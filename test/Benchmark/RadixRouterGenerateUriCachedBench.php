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
 * Benchmarks for the RadixRouter URI generation operation with caching enabled.
 */
#[BeforeMethods('setUp')]
class RadixRouterGenerateUriCachedBench
{
    private RadixRouter $router;
    private string $cacheFile;

    public function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/radix-router-generate-uri-cache-' . uniqid() . '.php';

        $this->router = new RadixRouter(config: [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => $this->cacheFile,
        ]);

        $this->router->addRoute(new Route('/api/users', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'users.list'));
        $this->router->addRoute(new Route('/api/users/:id', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'users.get'));
        $this->router->addRoute(new Route('/api/users/:id/profile', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'users.profile'));
        $this->router->addRoute(new Route('/api/posts', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.list'));
        $this->router->addRoute(new Route('/api/posts/:id', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.get'));
        $this->router->addRoute(new Route('/api/posts/:id/comments', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.comments'));
        $this->router->addRoute(new Route('/api/posts/:id/comments/:commentId', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'posts.comments.get'));

        $this->router->addRoute(new Route('/api/search/:query?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'search'));
        $this->router->addRoute(new Route('/api/filter/:category?/:subcategory?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'filter'));

        $this->router->addRoute(new Route('/api/products/:category/:subcategory?/:id?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'products'));
        $this->router->addRoute(new Route('/api/articles/:year/:month?/:day?/:slug?', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'articles'));

        $this->router->generateUri('users.list');
    }

    /**
     * Benchmark generating a URI for a simple route.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriSimpleRoute(): void
    {
        $this->router->generateUri('users.list');
    }

    /**
     * Benchmark generating a URI for a route with a single parameter.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithSingleParameter(): void
    {
        $this->router->generateUri('users.get', ['id' => '123']);
    }

    /**
     * Benchmark generating a URI for a route with multiple parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithMultipleParameters(): void
    {
        $this->router->generateUri('posts.comments.get', [
            'id' => '123',
            'commentId' => '456'
        ]);
    }

    /**
     * Benchmark generating a URI for a route with an optional parameter that is provided.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithOptionalParameterProvided(): void
    {
        $this->router->generateUri('search', ['query' => 'php']);
    }

    /**
     * Benchmark generating a URI for a route with an optional parameter that is omitted.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithOptionalParameterOmitted(): void
    {
        $this->router->generateUri('search');
    }

    /**
     * Benchmark generating a URI for a route with multiple optional parameters.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithMultipleOptionalParameters(): void
    {
        $this->router->generateUri('filter', [
            'category' => 'electronics',
            'subcategory' => 'laptops'
        ]);
    }

    /**
     * Benchmark generating a URI for a route with some optional parameters omitted.
     */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchGenerateUriWithSomeOptionalParametersOmitted(): void
    {
        $this->router->generateUri('articles', [
            'year' => '2023',
            'month' => '06',
            // day and slug omitted
        ]);
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