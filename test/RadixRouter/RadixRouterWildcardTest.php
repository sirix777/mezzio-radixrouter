<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use PHPUnit\Framework\Attributes\DataProvider;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;

use function str_contains;
use function str_replace;

class RadixRouterWildcardTest extends BaseRadixRouterTest
{
    public function testGenerateUriWithWildcardParameters(): void
    {
        $route = RouteBuilder::wildcard('/files/:path*', 'files');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('files', ['path' => 'documents/file.txt']);
        $this->assertSame('/files/documents/file.txt', $uri);
    }

    public function testGenerateUriWithOptionalWildcardParameterOmitted(): void
    {
        $route = RouteBuilder::wildcard('/files/:path*', 'files');
        $this->router->addRoute($route);

        $uri = $this->router->generateUri('files');
        $this->assertSame('/files', $uri);
    }

    public function testWildcardBehavior(): void
    {
        $route1 = RouteBuilder::withParams('/api/users/:id', 'user-get');
        $route2 = RouteBuilder::wildcard('/files/:path*', 'files-wildcard');

        $this->router->addRoute($route1);
        $this->router->addRoute($route2);

        $request1 = $this->createGetRequest('/api/users/123');
        $result1 = $this->router->match($request1);

        $this->assertRouteMatches($result1, 'user-get', ['id' => '123']);

        $request2 = $this->createGetRequest('/files');
        $result2 = $this->router->match($request2);

        $this->assertRouteMatches($result2, 'files-wildcard', ['path' => '']);

        $request3 = $this->createGetRequest('/files/readme.txt');
        $result3 = $this->router->match($request3);

        $this->assertRouteMatches($result3, 'files-wildcard', ['path' => 'readme.txt']);

        $request4 = $this->createGetRequest('/files/images/photo.jpg');
        $result4 = $this->router->match($request4);

        $this->assertRouteMatches($result4, 'files-wildcard', ['path' => 'images/photo.jpg']);
    }

    public function testWildcardWithOverlappingDynamicRoutes(): void
    {
        $route1 = RouteBuilder::withParams('/api/users/:id', 'user-get');
        $route2 = RouteBuilder::withParams('/api/users/:id/profile', 'user-profile');
        $route3 = RouteBuilder::wildcard('/api/users/:id/files/:filename*', 'user-files');

        $this->router->addRoute($route1);
        $this->router->addRoute($route2);
        $this->router->addRoute($route3);

        $request1 = $this->createGetRequest('/api/users/123');
        $result1 = $this->router->match($request1);
        $this->assertRoute($result1)
            ->shouldSucceed()
            ->withRouteName('user-get')
            ->withParams(['id' => '123'])
        ;

        $request2 = $this->createGetRequest('/api/users/123/profile');
        $result2 = $this->router->match($request2);
        $this->assertRoute($result2)
            ->shouldSucceed()
            ->withRouteName('user-profile')
            ->withParams(['id' => '123'])
        ;

        $request3 = $this->createGetRequest('/api/users/123/files/');
        $result3 = $this->router->match($request3);
        $this->assertRoute($result3)
            ->shouldSucceed()
            ->withRouteName('user-files')
            ->withParams(['id' => '123', 'filename' => ''])
        ;

        $request4 = $this->createGetRequest('/api/users/123/files/document.pdf');
        $result4 = $this->router->match($request4);
        $this->assertRoute($result4)
            ->shouldSucceed()
            ->withRouteName('user-files')
            ->withParams(['id' => '123', 'filename' => 'document.pdf'])
        ;

        $request5 = $this->createGetRequest('/api/users/123/files/folder/subfolder/file.txt');
        $result5 = $this->router->match($request5);
        $this->assertRoute($result5)
            ->shouldSucceed()
            ->withRouteName('user-files')
            ->withParams(['id' => '123', 'filename' => 'folder/subfolder/file.txt'])
        ;

        $request6 = $this->createGetRequest('/api/some/unknown/path');
        $result6 = $this->router->match($request6);
        $this->assertRoute($result6)->shouldFail();
    }

    public function testWildcardPriorityOrder(): void
    {
        $route1 = RouteBuilder::simple('/assets/css/main.css', 'main-css');
        $route2 = RouteBuilder::wildcard('/assets/:type/:filename*', 'assets-wildcard');

        $this->router->addRoute($route1);
        $this->router->addRoute($route2);

        $request1 = $this->createGetRequest('/assets/css/main.css');
        $result1 = $this->router->match($request1);
        $this->assertRouteMatches($result1, 'main-css');

        $request2 = $this->createGetRequest('/assets/js/app.min.js');
        $result2 = $this->router->match($request2);
        $this->assertRouteMatches($result2, 'assets-wildcard', ['type' => 'js', 'filename' => 'app.min.js']);

        $request3 = $this->createGetRequest('/assets/images/icons/favicon.ico');
        $result3 = $this->router->match($request3);
        $this->assertRouteMatches($result3, 'assets-wildcard', ['type' => 'images', 'filename' => 'icons/favicon.ico']);
    }

    public function testWildcardEmptyCapture(): void
    {
        $route = RouteBuilder::wildcard('/downloads/:path*', 'downloads');
        $this->router->addRoute($route);

        $request1 = $this->createGetRequest('/downloads');
        $result1 = $this->router->match($request1);
        $this->assertRouteMatches($result1, 'downloads', ['path' => '']);

        $request2 = $this->createGetRequest('/downloads/');
        $result2 = $this->router->match($request2);
        $this->assertRouteMatches($result2, 'downloads', ['path' => '']);
    }

    #[DataProvider('wildcardRoutesProvider')]
    public function testWildcardRoutes(string $path, string $name): void
    {
        /** @var non-empty-string $path */
        $path = '' !== $path ? $path : '/';

        $route = RouteBuilder::wildcard($path, $name);
        $this->router->addRoute($route);

        $testPath = $path;

        if (str_contains($path, ':path*')) {
            $testPath = str_replace(':path*', 'test/file.txt', $testPath);
        }

        if (str_contains($path, ':filename*')) {
            $testPath = str_replace(':filename*', 'docs/readme.md', $testPath);
        }

        if (str_contains($path, ':id')) {
            $testPath = str_replace(':id', '123', $testPath);
        }

        $request = $this->createGetRequest($testPath);
        $result = $this->router->match($request);

        $this->assertRoute($result)
            ->shouldSucceed()
            ->withRouteName($name)
        ;

        if (str_contains($path, ':path*')) {
            $this->assertArrayHasKey('path', $result->getMatchedParams());
            $this->assertSame('test/file.txt', $result->getMatchedParams()['path']);
        }

        if (str_contains($path, ':filename*')) {
            $this->assertArrayHasKey('filename', $result->getMatchedParams());
            $this->assertSame('docs/readme.md', $result->getMatchedParams()['filename']);
        }

        if (str_contains($path, ':id')) {
            $this->assertArrayHasKey('id', $result->getMatchedParams());
            $this->assertSame('123', $result->getMatchedParams()['id']);
        }

        if (str_contains($path, ':type')) {
            $this->assertArrayHasKey('type', $result->getMatchedParams());
        }
    }
}
