<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Exception\InvalidArgumentException;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Router\Enum\CacheConfig;
use Sirix\Mezzio\Router\Exception\InvalidCacheDirectoryException;
use Sirix\Mezzio\Router\RadixRouter;

class RadixRouterTest extends TestCase
{
    private RadixRouter $router;

    protected function setUp(): void
    {
        $this->router = new RadixRouter();
    }

    public function testAddingRouteAggregatesRoute(): void
    {
        $route = new Route('/foo', self::getMiddleware());
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($route, $result->getMatchedRoute());
    }

    public function testMatchingRouteShouldReturnSuccessfulRouteResult(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);

        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
        $matchedRoute = $result->getMatchedRoute();
        $this->assertNotFalse($matchedRoute);
        $this->assertSame($middleware, $matchedRoute->getMiddleware());
    }

    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/foo', $middleware, [RequestMethod::METHOD_POST]);

        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isMethodFailure());
        $this->assertSame([RequestMethod::METHOD_POST], $result->getAllowedMethods());
    }

    public function testMatchFailureNotDueToHttpMethodReturnsGenericRouteFailureResult(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/foo', $middleware);

        $this->router->addRoute($route);

        $request = $this->createServerRequest('/bar', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isMethodFailure());
    }

    /**
     * @param Route[]              $routes
     * @param array<string, mixed> $generateArgs
     */
    #[DataProvider('generatedUriProvider')]
    public function testCanGenerateUriFromRoutes(array $routes, string $expected, array $generateArgs): void
    {
        foreach ($routes as $route) {
            $this->router->addRoute($route);
        }

        $name = $generateArgs['name'];
        $substitutions = $generateArgs['substitutions'] ?? [];
        $options = $generateArgs['options'] ?? [];

        $uri = $this->router->generateUri($name, $substitutions, $options);
        $this->assertSame($expected, $uri);
    }

    /**
     * @return iterable<string, array{
     *     0: array<Route>,
     *     1: string,
     *     2: array<string, mixed>
     * }>
     */
    public static function generatedUriProvider(): iterable
    {
        yield 'simple' => [
            [
                new Route('/foo', self::getMiddleware(), [RequestMethod::METHOD_GET], 'foo'),
            ],
            '/foo',
            ['name' => 'foo'],
        ];

        yield 'with-param' => [
            [
                new Route('/foo/:id', self::getMiddleware(), [RequestMethod::METHOD_GET], 'foo'),
            ],
            '/foo/123',
            ['name' => 'foo', 'substitutions' => ['id' => '123']],
        ];

        yield 'with-optional-param-present' => [
            [
                new Route('/foo/:id?', self::getMiddleware(), [RequestMethod::METHOD_GET], 'foo'),
            ],
            '/foo/123',
            ['name' => 'foo', 'substitutions' => ['id' => '123']],
        ];

        yield 'with-optional-param-absent' => [
            [
                new Route('/foo/:id?', self::getMiddleware(), [RequestMethod::METHOD_GET], 'foo'),
            ],
            '/foo',
            ['name' => 'foo'],
        ];
    }

    public function testGenerateUriRaisesExceptionForMissingMandatoryParameters(): void
    {
        $route = new Route('/foo/:id', self::getMiddleware(), [RequestMethod::METHOD_GET], 'foo');
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

    public function testRouterConstructorWithConfiguration(): void
    {
        $config = [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => '/tmp/cache.php',
        ];

        $router = new RadixRouter(config: $config);
        $this->assertInstanceOf(RadixRouter::class, $router);
    }

    public function testLoadConfigWithNullConfiguration(): void
    {
        $router = new RadixRouter();
        $router->loadConfig(null);
        $this->expectNotToPerformAssertions(); // Just test it doesn't throw
    }

    public function testLoadConfigWithEmptyConfiguration(): void
    {
        $router = new RadixRouter();
        $router->loadConfig([]);
        $this->expectNotToPerformAssertions(); // Just test it doesn't throw
    }

    public function testLoadConfigWithCacheEnabled(): void
    {
        $router = new RadixRouter();
        $router->loadConfig([CacheConfig::Enabled->value => true]);
        $this->expectNotToPerformAssertions();
    }

    public function testLoadConfigWithCacheFile(): void
    {
        $router = new RadixRouter();
        $router->loadConfig([CacheConfig::File->value => '/tmp/cache.php']);
        $this->expectNotToPerformAssertions();
    }

    public function testMatchWithUrlDecodedPath(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/foo bar', $middleware, [RequestMethod::METHOD_GET]);
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo%20bar', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($route, $result->getMatchedRoute());
    }

    public function testMatchWithRouteParameters(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/user/:id', $middleware, [RequestMethod::METHOD_GET]);
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/user/123', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['id' => '123'], $result->getMatchedParams());
    }

    public function testMatchWithMultipleRouteParameters(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/user/:id/post/:postId', $middleware, [RequestMethod::METHOD_GET]);
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/user/123/post/456', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['id' => '123', 'postId' => '456'], $result->getMatchedParams());
    }

    public function testGenerateUriWithWildcardParameters(): void
    {
        $route = new Route('/files/:path*', self::getMiddleware(), [RequestMethod::METHOD_GET], 'files');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('files', ['path' => 'documents/file.txt']);
        $this->assertSame('/files/documents/file.txt', $uri);
    }

    public function testGenerateUriWithOptionalWildcardParameterOmitted(): void
    {
        $route = new Route('/files/:path*', self::getMiddleware(), [RequestMethod::METHOD_GET], 'files');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('files');
        $this->assertSame('/files', $uri);
    }

    public function testGenerateUriWithMultipleOptionalParameters(): void
    {
        $route = new Route('/user/:id?/posts/:postId?', self::getMiddleware(), [RequestMethod::METHOD_GET], 'user-posts');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('user-posts', ['id' => '123']);
        $this->assertSame('/user/123/posts', $uri);
    }

    public function testGenerateUriNormalizesPath(): void
    {
        $route = new Route('/user//posts', self::getMiddleware(), [RequestMethod::METHOD_GET], 'user-posts');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('user-posts');
        $this->assertSame('/user/posts', $uri);
    }

    public function testGenerateUriHandlesRootPath(): void
    {
        $route = new Route('/', self::getMiddleware(), [RequestMethod::METHOD_GET], 'root');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('root');
        $this->assertSame('/', $uri);
    }

    public function testGenerateUriWithMissingMultipleRequiredParameters(): void
    {
        $route = new Route('/user/:id/post/:postId', self::getMiddleware(), [RequestMethod::METHOD_GET], 'user-post');
        $this->router->addRoute($route);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing required parameters: id, postId');
        $this->router->generateUri('user-post');
    }

    public function testMatchWithMultipleHttpMethods(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/api/data', $middleware, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]);
        $this->router->addRoute($route);

        $getRequest = $this->createServerRequest('/api/data', RequestMethod::METHOD_GET);
        $getResult = $this->router->match($getRequest);
        $this->assertTrue($getResult->isSuccess());

        $postRequest = $this->createServerRequest('/api/data', RequestMethod::METHOD_POST);
        $postResult = $this->router->match($postRequest);
        $this->assertTrue($postResult->isSuccess());

        $putRequest = $this->createServerRequest('/api/data', RequestMethod::METHOD_PUT);
        $putResult = $this->router->match($putRequest);
        $this->assertFalse($putResult->isSuccess());
        $this->assertTrue($putResult->isMethodFailure());
        $this->assertEqualsCanonicalizing([RequestMethod::METHOD_GET, RequestMethod::METHOD_POST], $putResult->getAllowedMethods());
    }

    public function testAddRouteWithNullMethods(): void
    {
        $middleware = self::getMiddleware();
        $route = new Route('/foo', $middleware, null);
        $this->router->addRoute($route);

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_GET);
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess());
    }

    public function testAddRouteWithEmptyMethods(): void
    {
        $middleware = self::getMiddleware();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP methods argument was empty; must contain at least one method');

        new Route('/foo', $middleware, []);
    }

    public function testMultipleRoutesWithSamePathDifferentMethods(): void
    {
        $middleware = self::getMiddleware();
        $getRoute = new Route('/api/data', $middleware, [RequestMethod::METHOD_GET], 'get-data');
        $postRoute = new Route('/api/data', $middleware, [RequestMethod::METHOD_POST], 'post-data');

        $this->router->addRoute($getRoute);
        $this->router->addRoute($postRoute);

        $getRequest = $this->createServerRequest('/api/data', RequestMethod::METHOD_GET);
        $getResult = $this->router->match($getRequest);
        $this->assertTrue($getResult->isSuccess());
        $this->assertSame($getRoute, $getResult->getMatchedRoute());

        $postRequest = $this->createServerRequest('/api/data', RequestMethod::METHOD_POST);
        $postResult = $this->router->match($postRequest);
        $this->assertTrue($postResult->isSuccess());
        $this->assertSame($postRoute, $postResult->getMatchedRoute());
    }

    public function testCacheDirectoryCreationFailure(): void
    {
        $config = [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => '/root/readonly/cache.php',
        ];

        $router = new RadixRouter(config: $config);
        $route = new Route('/foo', self::getMiddleware());
        $router->addRoute($route);

        $this->expectException(InvalidCacheDirectoryException::class);
        $this->expectExceptionMessage('Failed to create cache directory');

        $request = $this->createServerRequest('/foo', RequestMethod::METHOD_GET);
        $router->match($request);
    }

    #[DataProvider('pathNormalizationProvider')]
    public function testPathNormalization(string $inputPath, string $expectedPath): void
    {
        // Ensure we always have a non-empty string for the Route constructor
        $path = '' === $inputPath ? '/' : $inputPath;
        $route = new Route($path, self::getMiddleware(), [RequestMethod::METHOD_GET], 'test');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('test');
        $this->assertSame($expectedPath, $uri);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pathNormalizationProvider(): iterable
    {
        yield 'double-slash' => ['/user//posts', '/user/posts'];

        yield 'triple-slash' => ['/user///posts', '/user/posts'];

        yield 'trailing-slash' => ['/user/posts/', '/user/posts'];

        yield 'multiple-trailing-slashes' => ['/user/posts///', '/user/posts'];

        yield 'empty-path' => ['', '/'];

        yield 'root-slash' => ['/', '/'];
    }

    private static function getMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new TextResponse('Hey There!');
            }
        };
    }

    private function createServerRequest(string $path, string $method): ServerRequestInterface
    {
        $uri = new Uri($path);

        return new ServerRequest([], [], $uri, $method);
    }
}
