<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Router;

use const E_WARNING;

use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Sirix\Mezzio\Router\Enum\CacheConfig;
use Sirix\Mezzio\Router\Exception\InvalidCacheDirectoryException;
use Wilaak\Http\RadixRouter as WilaakRadixRouter;

use function dirname;
use function file_exists;
use function file_put_contents;
use function implode;
use function is_array;
use function is_dir;
use function is_writable;
use function mkdir;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function rawurldecode;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function var_export;

/**
 * Router implementation using wilaak/radix-router.
 *
 * @phpstan-type RadixRouteConfig array{
 *    cache_enabled?: bool,
 *    cache_file?: string,
 * }
 */
class RadixRouter implements RouterInterface
{
    /**
     * Cached routes data.
     *
     * @var array<string, mixed>
     */
    private array $dispatchData = [];

    /**
     * Routes to add to the router.
     *
     * @var list<Route>
     */
    private array $routesToInject = [];

    /**
     * All attached routes, indexed by name.
     *
     * @var array<string, Route>
     */
    private array $routes = [];

    /**
     * True if cache is enabled and valid dispatch data has been loaded from the cache.
     */
    private bool $hasCache = false;

    private bool $cacheEnabled = false;

    private ?string $cacheFile = null;

    /**
     * Constructor.
     *
     * @param WilaakRadixRouter $router Router instance to use
     * @param null|array        $config Configuration options
     *
     * @phpstan-param null|RadixRouteConfig $config
     */
    public function __construct(private readonly WilaakRadixRouter $router = new WilaakRadixRouter(), ?array $config = null)
    {
        $this->loadConfig($config);
    }

    /**
     * Load configuration parameters.
     *
     * @param null|array $config Configuration options
     *
     * @phpstan-param null|RadixRouteConfig $config
     */
    public function loadConfig(?array $config): void
    {
        if (null === $config) {
            return;
        }

        if (isset($config[CacheConfig::Enabled->value])) {
            $this->cacheEnabled = $config[CacheConfig::Enabled->value];
        }

        if (isset($config[CacheConfig::File->value])) {
            $this->cacheFile = $config[CacheConfig::File->value];
        }
    }

    /**
     * Add a route to the collection.
     */
    public function addRoute(Route $route): void
    {
        $this->routesToInject[] = $route;
    }

    /**
     * Match a request against the known routes.
     */
    public function match(Request $request): RouteResult
    {
        $this->injectRoutes();

        $path = rawurldecode($request->getUri()->getPath());
        $method = $request->getMethod();

        $result = $this->router->lookup($method, $path);

        return match ($result['code']) {
            200 => $this->marshalMatchedRoute($result),
            405 => $this->marshalMethodNotAllowedResult($result),
            default => RouteResult::fromRouteFailure(null),
        };
    }

    /**
     * Generate a URI from the named route.
     *
     * @param string                $name          Name of the route
     * @param array<string, string> $substitutions Key/value pairs to substitute in the route
     * @param array<string, mixed>  $options       Options for the URI
     *
     * @return string URI generated from the route
     *
     * @throws RuntimeException If unable to generate the URI
     */
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        $this->injectRoutes();

        if (! isset($this->routes[$name])) {
            throw new RuntimeException(sprintf(
                'Cannot generate URI for route "%s"; route not found',
                $name
            ));
        }

        $route = $this->routes[$name];
        $path = $route->getPath();

        [$paramNames, $paramModifiers] = $this->extractRouteParameters($path);
        $this->validateRequiredParameters($paramNames, $paramModifiers, $substitutions, $route->getName());

        $path = $this->replacePathParameters($path, $paramNames, $substitutions);

        return $this->normalizePath($path);
    }

    /**
     * Extract route parameters from a path.
     *
     * @param string $path Route path
     *
     * @return array{0: array<string>, 1: array<string>} Array containing [paramNames, paramModifiers]
     */
    private function extractRouteParameters(string $path): array
    {
        preg_match_all('/:([^\/?*]+)([?*])?/', $path, $matches);

        return [$matches[1], $matches[2]];
    }

    /**
     * Validate required parameters are present.
     *
     * @param array<string>         $paramNames     Parameter names
     * @param array<string>         $paramModifiers Parameter modifiers
     * @param array<string, string> $substitutions  Parameter substitutions
     * @param string                $routeName      Route name for error messages
     *
     * @throws RuntimeException If required parameters are missing
     */
    private function validateRequiredParameters(array $paramNames, array $paramModifiers, array $substitutions, string $routeName): void
    {
        $missingParams = [];

        foreach ($paramNames as $index => $param) {
            $isOptional = isset($paramModifiers[$index])
                && ('?' === $paramModifiers[$index] || '*' === $paramModifiers[$index]);

            if (! isset($substitutions[$param]) && ! $isOptional) {
                $missingParams[] = $param;
            }
        }

        if ([] !== $missingParams) {
            throw new RuntimeException(sprintf(
                'Cannot generate URI for route "%s"; missing required parameters: %s',
                $routeName,
                implode(', ', $missingParams)
            ));
        }
    }

    /**
     * Replace parameters in a path with their values.
     *
     * @param string                $path          Original path
     * @param array<string>         $paramNames    Parameter names
     * @param array<string, string> $substitutions Parameter substitutions
     *
     * @return string Path with parameters replaced
     */
    private function replacePathParameters(string $path, array $paramNames, array $substitutions): string
    {
        $patterns = [];
        $replacements = [];

        foreach ($paramNames as $param) {
            if (isset($substitutions[$param])) {
                $patterns[] = sprintf('/:(%s)(\?|\*)?/', preg_quote($param));
                $replacements[] = $substitutions[$param];
            } else {
                // For optional parameters not provided, mark for removal
                $patterns[] = sprintf('/:(%s)(\?|\*)/', preg_quote($param));
                $replacements[] = '';
            }
        }

        if ([] !== $patterns) {
            $result = preg_replace($patterns, $replacements, $path);

            return $result ?? $path;
        }

        return $path;
    }

    /**
     * Normalize a path by removing duplicate slashes and ensuring proper format.
     *
     * @param string $path Path to normalize
     *
     * @return string Normalized path
     */
    private function normalizePath(string $path): string
    {
        // Clean up the path - remove duplicate slashes
        $result = preg_replace(['#/+#', '#/+$#'], ['/', ''], $path);
        $path = $result ?? $path;

        // Ensure root path has a leading slash
        if ('' === $path) {
            return '/';
        }

        return $path;
    }

    /**
     * Create a route result from a successful route match.
     *
     * @param array<string, mixed> $result Result from router
     *
     * @return RouteResult Route result
     */
    private function marshalMatchedRoute(array $result): RouteResult
    {
        $route = &$this->routes[$result['handler']];

        return RouteResult::fromRoute($route, $result['params'] ?? []);
    }

    /**
     * Create a route result from a method not allowed result.
     *
     * @param array<string, mixed> $result Result from router
     *
     * @return RouteResult Route result
     */
    private function marshalMethodNotAllowedResult(array $result): RouteResult
    {
        return RouteResult::fromRouteFailure($result['allowed_methods'] ?? []);
    }

    /**
     * Inject routes into the underlying router.
     */
    private function injectRoutes(): void
    {
        if ([] === $this->routesToInject) {
            return;
        }

        if ($this->cacheEnabled) {
            $this->loadDispatchData();
        }

        foreach ($this->routesToInject as $route) {
            $this->injectRoute($route);
        }
        $this->routesToInject = [];

        if ([] !== $this->dispatchData) {
            $this->router->tree = $this->dispatchData['tree'] ?? [];
            $this->router->static = $this->dispatchData['static'] ?? [];

            return;
        }

        if ($this->cacheEnabled) {
            $this->cacheDispatchData();
        }
    }

    /**
     * Inject a route into the underlying router.
     */
    private function injectRoute(Route $route): void
    {
        $methods = $route->getAllowedMethods();

        // Convert null/empty methods to ANY
        if (null === $methods || [] === $methods) {
            $methods = $this->router->allowedMethods;
        }

        $this->routes[$route->getName()] = $route;

        if ($this->hasCache) {
            return;
        }

        $this->router->add($methods, $route->getPath(), $route->getName());
    }

    /**
     * Execute a callback with error suppression.
     *
     * @template T
     *
     * @param callable():T $callback Function to execute with error suppression
     *
     * @return T The return value of the callback
     */
    private function executeWithErrorSuppression(callable $callback)
    {
        set_error_handler(fn () => true, E_WARNING);

        $result = $callback();

        restore_error_handler();

        return $result;
    }

    /**
     * Load dispatch data from cache.
     */
    private function loadDispatchData(): void
    {
        if (! $this->cacheFile || ! file_exists($this->cacheFile)) {
            return;
        }

        if ($this->hasCache) {
            return;
        }

        $dispatchData = $this->executeWithErrorSuppression(fn () => require $this->cacheFile);

        if (! is_array($dispatchData)) {
            return;
        }

        $this->hasCache = true;
        $this->dispatchData = $dispatchData;
    }

    /**
     * Cache dispatch data.
     */
    private function cacheDispatchData(): void
    {
        if (! $this->cacheFile) {
            return;
        }

        $dispatchData = [
            'tree' => $this->router->tree,
            'static' => $this->router->static,
        ];

        $this->ensureCacheDirectoryExists();

        $this->executeWithErrorSuppression(function() use ($dispatchData) {
            if (null !== $this->cacheFile) {
                file_put_contents(
                    $this->cacheFile,
                    sprintf("<?php\nreturn %s;", var_export($dispatchData, true))
                );
            }
        });
    }

    /**
     * Ensure the cache directory exists and is writable.
     *
     * @throws InvalidCacheDirectoryException If a directory cannot be created or is not writable
     */
    private function ensureCacheDirectoryExists(): void
    {
        $cacheDir = dirname((string) $this->cacheFile);

        if (! is_dir($cacheDir) && ! @mkdir($cacheDir, 0o775, true)) {
            throw new InvalidCacheDirectoryException(sprintf(
                'Failed to create cache directory "%s"',
                $cacheDir
            ));
        }

        if (! is_writable($cacheDir)) {
            throw new InvalidCacheDirectoryException(sprintf(
                'Cache directory "%s" is not writable',
                $cacheDir
            ));
        }
    }
}
