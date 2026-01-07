<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use InvalidArgumentException;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;

class RadixRouterUriGenerationTest extends BaseRadixRouterTest
{
    /**
     * @param array<array<int, mixed>> $routeData
     * @param array<string, mixed>     $generateArgs
     */
    #[DataProvider('uriGenerationProvider')]
    public function testCanGenerateUriFromRoutes(array $routeData, string $expected, array $generateArgs): void
    {
        foreach ($routeData as $data) {
            $route = new Route($data[0], $this->middleware, $data[2] ?? [RequestMethod::METHOD_GET], $data[1]);
            $this->router->addRoute($route);
        }

        $name = $generateArgs['name'];
        $substitutions = $generateArgs['substitutions'] ?? [];
        $options = $generateArgs['options'] ?? [];

        $uri = $this->router->generateUri($name, $substitutions, $options);
        $this->assertSame($expected, $uri);
    }

    public function testGenerateUriWithEmptySegmentsThrows(): void
    {
        $route = RouteBuilder::simple('/user//posts', 'user-posts');
        $this->router->addRoute($route);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Empty segments are not allowed/');

        $this->router->generateUri('user-posts');
    }

    public function testGenerateUriHandlesRootPath(): void
    {
        $route = RouteBuilder::simple('/', 'root');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('root');
        $this->assertSame('/', $uri);
    }

    public function testMatchWithUrlDecodedPath(): void
    {
        $route = RouteBuilder::simple('/foo bar', 'foo-bar');
        $this->router->addRoute($route);

        $request = $this->createGetRequest('/foo%20bar');
        $result = $this->router->match($request);

        $this->assertRouteMatches($result, 'foo-bar');
    }

    public function testTrailingSlashNormalization(): void
    {
        $route = RouteBuilder::simple('/user', 'user');
        $this->router->addRoute($route);

        $request1 = $this->createGetRequest('/user');
        $result1 = $this->router->match($request1);
        $this->assertRouteMatches($result1, 'user');

        $request2 = $this->createGetRequest('/user/');
        $result2 = $this->router->match($request2);
        $this->assertRouteMatches($result2, 'user');

        $this->assertSame($result1->getMatchedRoute(), $result2->getMatchedRoute());
    }

    public function testMultipleRoutesWithSamePathDifferentMethods(): void
    {
        $getRoute = RouteBuilder::simple('/api/data', 'get-data');
        $postRoute = RouteBuilder::post('/api/data', 'post-data');

        $this->router->addRoute($getRoute);
        $this->router->addRoute($postRoute);

        $getRequest = $this->createGetRequest('/api/data');
        $getResult = $this->router->match($getRequest);
        $this->assertRouteMatches($getResult, 'get-data');

        $postRequest = $this->createPostRequest('/api/data');
        $postResult = $this->router->match($postRequest);
        $this->assertRouteMatches($postResult, 'post-data');
    }

    public function testMatchWithMultipleHttpMethods(): void
    {
        $route = RouteBuilder::withMethods('/api/data', [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST], 'api-data');
        $this->router->addRoute($route);

        $getRequest = $this->createGetRequest('/api/data');
        $getResult = $this->router->match($getRequest);
        $this->assertRouteMatches($getResult, 'api-data');

        $postRequest = $this->createPostRequest('/api/data');
        $postResult = $this->router->match($postRequest);
        $this->assertRouteMatches($postResult, 'api-data');

        $putRequest = $this->createPutRequest('/api/data');
        $putResult = $this->router->match($putRequest);
        $this->assertMethodNotAllowed($putResult, [RequestMethod::METHOD_GET, RequestMethod::METHOD_HEAD, RequestMethod::METHOD_POST]);
    }
}
