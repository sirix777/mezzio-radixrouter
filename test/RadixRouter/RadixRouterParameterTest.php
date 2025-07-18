<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Mezzio\Router\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;

use function str_contains;
use function str_replace;

class RadixRouterParameterTest extends BaseRadixRouterTest
{
    public function testMatchWithRouteParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id', 'user-get');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/user/123');
        $result = $this->router->match($request);

        $this->assertRouteMatches($result, 'user-get', ['id' => '123']);
    }

    public function testMatchWithMultipleRouteParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id/post/:postId', 'user-post');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/user/123/post/456');
        $result = $this->router->match($request);

        $this->assertRouteMatches($result, 'user-post', ['id' => '123', 'postId' => '456']);
    }

    public function testGenerateUriRaisesExceptionForMissingMandatoryParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id', 'user-get');
        $this->router->addRoute($route);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing required parameter');
        $this->router->generateUri('user-get');
    }

    public function testGenerateUriWithOptionalParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id/posts/:postId?', 'user-posts');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('user-posts', ['id' => '123']);
        $this->assertSame('/user/123/posts', $uri);
    }

    public function testGenerateUriWithMissingMultipleRequiredParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id/post/:postId', 'user-post');
        $this->router->addRoute($route);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing required parameters: id, postId');
        $this->router->generateUri('user-post');
    }

    public function testMultipleOptionalParametersChain(): void
    {
        $route = RouteBuilder::withParams('/archive/:year?/:month?/:day?', 'archive');
        $this->router->addRoute($route);

        $this->assertSame('/archive', $this->router->generateUri('archive'));
        $this->assertSame('/archive/2023', $this->router->generateUri('archive', ['year' => '2023']));
        $this->assertSame('/archive/2023/06', $this->router->generateUri('archive', ['year' => '2023', 'month' => '06']));
        $this->assertSame('/archive/2023/06/15', $this->router->generateUri('archive', ['year' => '2023', 'month' => '06', 'day' => '15']));
    }

    #[DataProvider('parametricRoutesProvider')]
    public function testParametricRoutes(string $path, string $name): void
    {
        /** @var non-empty-string $path */
        $path = '' !== $path ? $path : '/';

        $route = RouteBuilder::withParams($path, $name);
        $this->router->addRoute($route);

        $paramPath = $path;
        if (str_contains($path, ':id')) {
            $paramPath = str_replace(':id', '123', $paramPath);
        }

        if (str_contains($path, ':query')) {
            $paramPath = str_replace(':query', 'test', $paramPath);
        }

        if (str_contains($path, ':postId')) {
            $paramPath = str_replace(':postId', '456', $paramPath);
        }
        $paramPath = str_replace('?', '', $paramPath); // Remove optional marker

        $request = $this->createGetRequest($paramPath);
        $result = $this->router->match($request);

        $this->assertRoute($result)
            ->shouldSucceed()
            ->withRouteName($name)
        ;

        if (str_contains($path, ':id')) {
            $this->assertArrayHasKey('id', $result->getMatchedParams());
            $this->assertSame('123', $result->getMatchedParams()['id']);
        }

        if (str_contains($path, ':query')) {
            $this->assertArrayHasKey('query', $result->getMatchedParams());
            $this->assertSame('test', $result->getMatchedParams()['query']);
        }

        if (str_contains($path, ':postId')) {
            $this->assertArrayHasKey('postId', $result->getMatchedParams());
            $this->assertSame('456', $result->getMatchedParams()['postId']);
        }
    }
}
