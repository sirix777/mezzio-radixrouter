<?php

declare(strict_types=1);

namespace Sirix\Router\RadixRouter\BenchmarkComparison;

use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Route;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class MezzioFastRouteCachedAdapter implements RouterInterface
{
    private ?FastRouteRouter $router = null;
    private string $cacheFile = '';

    public function mount(string $tmpFile): void
    {
        $this->cacheFile = $tmpFile;
    }

    public function adapt(array $routes): array
    {
        return $routes;
    }

    public function register(array $adaptedRoutes): void
    {
        $this->router = new FastRouteRouter(
            null,
            null,
            [
                'cache_enabled' => true,
                'cache_file' => $this->cacheFile,
            ]
        );

        foreach ($adaptedRoutes as $pattern) {
            $middleware = new BenchmarkMiddleware(function () {
                return 'ok';
            });
            $route = new Route($pattern, $middleware, ['GET'], $pattern);
            $this->router->addRoute($route);
        }
    }

    public function match(ServerRequestInterface $request): void
    {
        if ($this->router === null) {
            throw new RuntimeException('Router not initialized');
        }

        $result = $this->router->match($request);
        if ($result->isFailure()) {
            throw new RuntimeException('Route not found: ' . $request->getUri()->getPath());
        }
    }

    public static function details(): array
    {
        return [
            'name' => 'MezzioFastRouteCached',
            'description' => 'Mezzio FastRoute adapter with caching (nikic/fast-route)',
        ];
    }
}
