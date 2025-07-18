<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Exception\InvalidArgumentException;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use Sirix\Mezzio\Router\Exception\InvalidCacheDirectoryException;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;
use SirixTest\Mezzio\Router\Utilities\RouterConfigFactory;

class RadixRouterErrorTest extends BaseRadixRouterTest
{
    public function testAddRouteWithEmptyMethods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP methods argument was empty; must contain at least one method');

        new Route('/foo', $this->middleware, []);
    }

    public function testGenerateUriRaisesExceptionForMissingMandatoryParameters(): void
    {
        $route = RouteBuilder::withParams('/foo/:id', 'foo');
        $this->router->addRoute($route);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing required parameter');
        $this->router->generateUri('foo');
    }

    public function testGenerateUriRaisesExceptionForNotFoundRoute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('route not found');
        $this->router->generateUri('foo');
    }

    public function testGenerateUriWithMissingMultipleRequiredParameters(): void
    {
        $route = RouteBuilder::withParams('/user/:id/post/:postId', 'user-post');
        $this->router->addRoute($route);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing required parameters: id, postId');
        $this->router->generateUri('user-post');
    }

    public function testCacheDirectoryCreationFailure(): void
    {
        $config = RouterConfigFactory::withInvalidCacheDirectory();
        $router = $this->createRouterWithConfig($config);

        $route = RouteBuilder::simple('/foo', 'foo');
        $router->addRoute($route);

        $this->expectException(InvalidCacheDirectoryException::class);
        $this->expectExceptionMessage('Failed to create cache directory');

        $request = $this->createGetRequest('/foo');
        $router->match($request);
    }

    public function testMatchWithNonExistentRoute(): void
    {
        $route = RouteBuilder::simple('/foo', 'foo');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/bar');
        $result = $this->router->match($request);

        $this->assertRouteNotFound($result);
    }

    public function testMatchWithMethodNotAllowed(): void
    {
        $route = RouteBuilder::post('/foo', 'foo-post');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertMethodNotAllowed($result, [RequestMethod::METHOD_POST]);
    }

    public function testMatchWithMultipleMethodsNotAllowed(): void
    {
        $route = RouteBuilder::withMethods('/foo', [RequestMethod::METHOD_POST, RequestMethod::METHOD_PUT], 'foo-methods');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertMethodNotAllowed($result, [RequestMethod::METHOD_POST, RequestMethod::METHOD_PUT]);
    }
}
