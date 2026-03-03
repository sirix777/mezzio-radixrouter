<?php

declare(strict_types=1);

namespace Sirix\Router\RadixRouter\BenchmarkComparison;

use Mezzio\Router\Route;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Sirix\Mezzio\Router\RadixRouter;

class MezzioRadixRouterAdapter implements RouterInterface
{
    private ?RadixRouter $router = null;

    public function mount(string $tmpFile): void
    {
    }

    public function adapt(array $routes): array
    {
        return array_map(function ($route) {
            return preg_replace('/\{([^}]+)\}/', ':$1', $route);
        }, $routes);
    }

    public function register(array $adaptedRoutes): void
    {
        $this->router = new RadixRouter();

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
            'name' => 'MezzioRadixRouter',
            'description' => 'Mezzio RadixRouter adapter (sirix/mezzio-radixrouter)',
        ];
    }
}
