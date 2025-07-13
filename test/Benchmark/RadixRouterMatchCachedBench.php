<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Benchmark;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Router\Enum\CacheConfig;
use Sirix\Mezzio\Router\RadixRouter;

use function file_exists;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Benchmarks for the RadixRouter match operation with caching enabled.
 */
#[BeforeMethods('setUp')]
class RadixRouterMatchCachedBench
{
    private RadixRouter $router;
    private string $cacheFile;

    /**
     * Clean up the cache file after the benchmark.
     */
    public function __destruct()
    {
        if (file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }

    public function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/radix-router-match-cache-' . uniqid() . '.php';

        $this->router = new RadixRouter(config: [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => $this->cacheFile,
        ]);

        $this->router->addRoute(new Route('/api/users', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'users.list'));
        $this->router->addRoute(new Route('/api/users/:id', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'users.get'));
        $this->router->addRoute(new Route('/api/users/:id', $this->getMiddleware(), [RequestMethod::METHOD_PUT], 'users.update'));
        $this->router->addRoute(new Route('/api/users/:id', $this->getMiddleware(), [RequestMethod::METHOD_DELETE], 'users.delete'));
        $this->router->addRoute(new Route('/api/posts', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.list'));
        $this->router->addRoute(new Route('/api/posts/:id', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.get'));
        $this->router->addRoute(new Route('/api/posts/:id/comments', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.comments'));
        $this->router->addRoute(new Route('/api/posts/:id/comments/:commentId', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.comments.get'));
        $this->router->addRoute(new Route('/api/tags', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'tags.list'));
        $this->router->addRoute(new Route('/api/tags/:tag', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'tags.get'));

        $this->router->addRoute(new Route('/api/search/:query?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'search'));
        $this->router->addRoute(new Route('/api/filter/:category?/:subcategory?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'filter'));

        $this->router->addRoute(new Route('/api/resources', $this->getMiddleware(), [
            RequestMethod::METHOD_GET,
            RequestMethod::METHOD_POST,
            RequestMethod::METHOD_PUT,
            RequestMethod::METHOD_DELETE,
        ], 'resources'));

        $request = $this->createServerRequest('/api/users', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a simple route.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchSimpleRoute(): void
    {
        $request = $this->createServerRequest('/api/users', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a route with parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchRouteWithParameters(): void
    {
        $request = $this->createServerRequest('/api/users/123', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a route with multiple parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchRouteWithMultipleParameters(): void
    {
        $request = $this->createServerRequest('/api/posts/123/comments/456', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a route with optional parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchRouteWithOptionalParameters(): void
    {
        $request = $this->createServerRequest('/api/search/php', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a route with multiple HTTP methods.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchRouteWithMultipleHttpMethods(): void
    {
        $request = $this->createServerRequest('/api/resources', RequestMethod::METHOD_POST);
        $this->router->match($request);
    }

    /**
     * Benchmark matching a non-existent route.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMatchNonExistentRoute(): void
    {
        $request = $this->createServerRequest('/api/non-existent', RequestMethod::METHOD_GET);
        $this->router->match($request);
    }

    /**
     * Create a server request with the given path and method.
     */
    private function createServerRequest(string $path, string $method): ServerRequestInterface
    {
        $uri = new Uri($path);

        return new ServerRequest([], [], $uri, $method);
    }

    /**
     * Get a simple middleware for testing.
     */
    private function getMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };
    }
}
