<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Benchmark;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Route;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Sirix\Mezzio\Router\Enum\CacheConfig;
use Sirix\Mezzio\Router\RadixRouter;

/**
 * Benchmarks for the RadixRouter route addition operation with caching enabled.
 */
#[BeforeMethods('setUp')]
class RadixRouterAddRouteCachedBench
{
    private RadixRouter $router;
    private MiddlewareInterface $middleware;
    private string $cacheFile;

    public function setUp(): void
    {
        // Create a temporary cache file
        $this->cacheFile = sys_get_temp_dir() . '/radix-router-add-route-cache-' . uniqid() . '.php';
        
        // Initialize router with caching enabled
        $this->router = new RadixRouter(config: [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => $this->cacheFile,
        ]);
        
        $this->middleware = $this->getMiddleware();
    }

    /**
     * Benchmark adding a simple route.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddSimpleRoute(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/simple', $this->middleware, [RequestMethod::METHOD_GET], 'simple'));
    }

    /**
     * Benchmark adding a route with parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRouteWithParameters(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/users/:id', $this->middleware, [RequestMethod::METHOD_GET], 'users.get'));
    }

    /**
     * Benchmark adding a route with multiple parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRouteWithMultipleParameters(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/posts/:id/comments/:commentId', $this->middleware, [RequestMethod::METHOD_GET], 'posts.comments.get'));
    }

    /**
     * Benchmark adding a route with optional parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRouteWithOptionalParameters(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/search/:query?', $this->middleware, [RequestMethod::METHOD_GET], 'search'));
    }

    /**
     * Benchmark adding a route with multiple optional parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRouteWithMultipleOptionalParameters(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/filter/:category?/:subcategory?', $this->middleware, [RequestMethod::METHOD_GET], 'filter'));
    }

    /**
     * Benchmark adding a route with multiple HTTP methods.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRouteWithMultipleHttpMethods(): void
    {
        $router = clone $this->router;
        $router->addRoute(new Route('/api/resources', $this->middleware, [
            RequestMethod::METHOD_GET,
            RequestMethod::METHOD_POST,
            RequestMethod::METHOD_PUT,
            RequestMethod::METHOD_DELETE,
        ], 'resources'));
    }

    /**
     * Benchmark adding multiple routes.
     */
    #[Revs(100)]
    #[Iterations(5)]
    public function benchAddMultipleRoutes(): void
    {
        $router = clone $this->router;
        
        // Add 10 routes with different patterns
        for ($i = 1; $i <= 10; $i++) {
            $router->addRoute(new Route(
                sprintf('/api/resource-%d/:id', $i),
                $this->middleware,
                [RequestMethod::METHOD_GET],
                sprintf('resource.%d', $i)
            ));
        }
    }

    /**
     * Benchmark adding routes with same path but different methods.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchAddRoutesWithSamePathDifferentMethods(): void
    {
        $router = clone $this->router;
        
        $router->addRoute(new Route('/api/users/:id', $this->middleware, [RequestMethod::METHOD_GET], 'users.get'));
        $router->addRoute(new Route('/api/users/:id', $this->middleware, [RequestMethod::METHOD_PUT], 'users.update'));
        $router->addRoute(new Route('/api/users/:id', $this->middleware, [RequestMethod::METHOD_DELETE], 'users.delete'));
    }

    /**
     * Get a simple middleware for testing.
     */
    private function getMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }
    
    /**
     * Clean up the cache file after the benchmark.
     */
    public function __destruct()
    {
        if (file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }
}