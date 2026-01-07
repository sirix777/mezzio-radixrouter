<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use InvalidArgumentException;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Sirix\Mezzio\Router\RadixRouter;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;
use SirixTest\Mezzio\Router\Utilities\RouterConfigFactory;

class RadixRouterBasicTest extends BaseRadixRouterTest
{
    public function testAddingRouteAggregatesRoute(): void
    {
        $route = RouteBuilder::simple('/foo');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertRouteMatches($result, $route->getName());
        $this->assertSame($route, $result->getMatchedRoute());
    }

    public function testMatchingRouteShouldReturnSuccessfulRouteResult(): void
    {
        $route = RouteBuilder::simple('/foo', 'foo-route');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertRoute($result)
            ->shouldSucceed()
            ->withRouteName('foo-route')
        ;
    }

    public function testMatchingHeadRouteShouldReturnSuccessfulRouteResultIfGetMethodIsAllowed(): void
    {
        $route = RouteBuilder::simple('/foo', 'foo-route');
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_HEAD);
        $result = $this->router->match($request);

        $this->assertRoute($result)
            ->shouldSucceed()
            ->withRouteName('foo-route')
        ;
    }

    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods(): void
    {
        $route = RouteBuilder::post('/foo', 'foo-post');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertMethodNotAllowed($result, [RequestMethod::METHOD_POST]);
    }

    public function testMatchFailureNotDueToHttpMethodReturnsGenericRouteFailureResult(): void
    {
        $route = RouteBuilder::simple('/foo', 'foo-route');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/bar');
        $result = $this->router->match($request);

        $this->assertRouteNotFound($result);
    }

    public function testRouterConstructorWithConfiguration(): void
    {
        $config = RouterConfigFactory::withCache();
        $router = $this->createRouterWithConfig($config);

        $this->assertInstanceOf(RadixRouter::class, $router);
    }

    public function testLoadConfigWithNullConfiguration(): void
    {
        $this->router->loadConfig(null);
        $this->expectNotToPerformAssertions();
    }

    public function testLoadConfigWithEmptyConfiguration(): void
    {
        $this->router->loadConfig([]);
        $this->expectNotToPerformAssertions();
    }

    public function testLoadConfigWithCacheEnabled(): void
    {
        $this->router->loadConfig(RouterConfigFactory::withCache());
        $this->expectNotToPerformAssertions();
    }

    public function testAddRouteWithNullMethods(): void
    {
        $route = RouteBuilder::any('/foo', 'foo-any');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo');
        $result = $this->router->match($request);

        $this->assertRouteMatches($result, 'foo-any');
    }

    public function testGenerateUriRaisesExceptionForNotFoundRoute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('route not found');
        $this->router->generateUri('foo');
    }

    #[DataProvider('pathNormalizationProvider')]
    public function testPathNormalization(string $inputPath, string $expectedPath): void
    {
        // Ensure we always have a non-empty string for the Route constructor
        $path = '' === $inputPath ? '/' : $inputPath;
        $route = new Route($path, $this->middleware, [RequestMethod::METHOD_GET], 'test');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('test');
        $this->assertSame($expectedPath, $uri);
    }

    #[DataProvider('invalidRoutePatternsProvider')]
    public function testEmptySegmentsInRoutePatternShouldThrow(string $inputPath): void
    {
        $path = '' === $inputPath ? '/' : $inputPath;
        $route = new Route($path, $this->middleware, [RequestMethod::METHOD_GET], 'invalid');
        $this->router->addRoute($route);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Empty segments are not allowed/');

        // Exception will be thrown during route injection triggered by URI generation
        $this->router->generateUri('invalid');
    }
}
