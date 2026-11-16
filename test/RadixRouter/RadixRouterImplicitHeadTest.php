<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Mezzio\Router\RouteResult;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;
use SirixTest\Mezzio\Router\Utilities\RouterConfigFactory;

use function file_exists;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class RadixRouterImplicitHeadTest extends BaseRadixRouterTest
{
    private string $cacheFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheFile = sys_get_temp_dir() . '/radix-head-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testSeparateGetAndHeadRoutesOnSamePathDoNotConflict(): void
    {
        $this->router->addRoute(RouteBuilder::withMethods('/foo', ['GET'], 'foo-get'));
        $this->router->addRoute(RouteBuilder::withMethods('/foo', ['HEAD'], 'foo-head'));

        $this->assertRouteMatchesWithoutParams($this->matchRequest('HEAD', '/foo'), 'foo-head');
        $this->assertRouteMatchesWithoutParams($this->matchRequest('GET', '/foo'), 'foo-get');
    }

    public function testRouteAllowingGetAndHeadMatchesHead(): void
    {
        $this->router->addRoute(RouteBuilder::withMethods('/foo', ['GET', 'HEAD'], 'foo'));

        $this->assertRouteMatchesWithoutParams($this->matchRequest('HEAD', '/foo'), 'foo');
    }

    public function testAnyMethodRouteMatchesHead(): void
    {
        $this->router->addRoute(RouteBuilder::any('/foo', 'foo-any'));

        $this->assertRouteMatchesWithoutParams($this->matchRequest('HEAD', '/foo'), 'foo-any');
    }

    public function testImplicitHeadOnWildcardAndOptionalRoutes(): void
    {
        $this->router->addRoute(RouteBuilder::withMethods('/files/:path*', ['GET'], 'files'));
        $this->router->addRoute(RouteBuilder::withMethods('/posts/:id?', ['GET'], 'posts'));

        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/files/a/b'), ['GET']);
        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/posts'), ['GET']);
        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/posts/5'), ['GET']);
    }

    public function testAllowedMethodsUseDecodedPath(): void
    {
        $this->router->addRoute(RouteBuilder::withMethods('/files/:name', ['GET'], 'file'));

        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/files/a%20b'), ['GET']);
    }

    /**
     * The more specific static route wins over an explicit HEAD on a parameter
     * route, unlike FastRoute, which would pick the explicit HEAD route.
     */
    public function testOverlappingStaticAndParameterRoutes(): void
    {
        $this->router->addRoute(RouteBuilder::withMethods('/users/me', ['GET'], 'me'));
        $this->router->addRoute(RouteBuilder::withMethods('/users/:id', ['HEAD'], 'by-id-head'));
        $this->router->addRoute(RouteBuilder::withMethods('/users/:id', ['POST'], 'by-id-post'));

        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/users/me'), ['GET', 'HEAD', 'POST']);
        $this->assertRouteMatchesWithoutParams($this->matchRequest('HEAD', '/users/7'), 'by-id-head');
        $this->assertRouteMatchesWithoutParams($this->matchRequest('POST', '/users/me'), 'by-id-post');
        $this->assertMethodNotAllowed($this->matchRequest('PUT', '/users/me'), ['GET', 'HEAD', 'POST']);
        $this->assertRouteMatchesWithoutParams($this->matchRequest('GET', '/users/me'), 'me');
    }

    public function testImplicitHeadFailsWhenRoutesComeFromCache(): void
    {
        $config = RouterConfigFactory::withCache($this->cacheFile);

        $writer = $this->createRouterWithConfig($config);
        $writer->addRoute(RouteBuilder::withMethods('/foo', ['GET'], 'foo'));
        $writer->match($this->createServerRequest('/foo', 'GET'));
        $this->assertFileExists($this->cacheFile);

        $this->router = $this->createRouterWithConfig($config);
        $this->router->addRoute(RouteBuilder::withMethods('/foo', ['GET'], 'foo'));

        $this->assertMethodNotAllowed($this->matchRequest('HEAD', '/foo'), ['GET']);
        $this->assertRouteMatchesWithoutParams($this->matchRequest('GET', '/foo'), 'foo');
    }

    /**
     * Cache files written by 3.2.x contain a HEAD entry for every GET route.
     */
    public function testImplicitHeadFailsWithCacheFileContainingInjectedHead(): void
    {
        $config = RouterConfigFactory::withCache($this->cacheFile);

        $writer = $this->createRouterWithConfig($config);
        $writer->addRoute(RouteBuilder::withMethods('/foo', ['GET', 'HEAD'], 'foo'));
        $writer->match($this->createServerRequest('/foo', 'GET'));
        $this->assertFileExists($this->cacheFile);

        $this->router = $this->createRouterWithConfig($config);
        $this->router->addRoute(RouteBuilder::withMethods('/foo', ['GET'], 'foo'));

        $result = $this->matchRequest('HEAD', '/foo');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isMethodFailure());
    }

    private function matchRequest(string $method, string $path): RouteResult
    {
        return $this->router->match($this->createServerRequest($path, $method));
    }
}
