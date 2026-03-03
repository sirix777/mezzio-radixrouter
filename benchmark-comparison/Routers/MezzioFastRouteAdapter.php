<?php

declare(strict_types=1);

namespace Sirix\Router\RadixRouter\BenchmarkComparison;

use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Route;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class MezzioFastRouteAdapter implements RouterInterface
{
    private ?FastRouteRouter $router = null;

    public function mount(string $tmpFile): void
    {
    }

    public function adapt(array $routes): array
    {
        return $routes;
    }

    public function register(array $adaptedRoutes): void
    {
        $this->router = new FastRouteRouter();

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
            'name' => 'MezzioFastRoute',
            'description' => 'Mezzio FastRoute adapter (nikic/fast-route)',
        ];
    }
}
