<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Traits;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;

trait RouteDataProviders
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function simpleRoutesProvider(): iterable
    {
        yield 'basic' => ['/users', 'users'];

        yield 'with-segment' => ['/api/users', 'api-users'];

        yield 'nested' => ['/api/v1/users', 'api-v1-users'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function parametricRoutesProvider(): iterable
    {
        yield 'single-param' => ['/users/:id', 'users-get'];

        yield 'multiple-params' => ['/users/:id/posts/:postId', 'user-posts'];

        yield 'optional-param' => ['/search/:query?', 'search'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function wildcardRoutesProvider(): iterable
    {
        yield 'simple-wildcard' => ['/files/:path*', 'files'];

        yield 'nested-wildcard' => ['/api/users/:id/files/:filename*', 'user-files'];

        yield 'typed-wildcard' => ['/assets/:type/:filename*', 'assets'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pathNormalizationProvider(): iterable
    {
        // Empty segments (e.g., //) are no longer normalized; they now throw.
        // See invalidRoutePatternsProvider() for those cases.

        yield 'trailing-slash' => ['/user/posts/', '/user/posts'];

        yield 'empty-path' => ['', '/'];

        yield 'root-slash' => ['/', '/'];
    }

    /**
     * Route patterns that contain empty segments must now trigger an exception.
     *
     * @return iterable<string, array{string}>
     */
    public static function invalidRoutePatternsProvider(): iterable
    {
        yield 'double-slash' => ['/user//posts'];

        yield 'triple-slash' => ['/user///posts'];

        yield 'multiple-trailing-slashes' => ['/user/posts///'];
    }

    /**
     * @return iterable<string, array{
     *     0: array<array{string, string, array<string>}>,
     *     1: string,
     *     2: array<string, mixed>
     * }>
     */
    public static function uriGenerationProvider(): iterable
    {
        yield 'simple' => [
            [['/foo', 'foo', [RequestMethod::METHOD_GET]]],
            '/foo',
            ['name' => 'foo'],
        ];

        yield 'with-param' => [
            [['/foo/:id', 'foo', [RequestMethod::METHOD_GET]]],
            '/foo/123',
            ['name' => 'foo', 'substitutions' => ['id' => '123']],
        ];

        yield 'with-optional-param-present' => [
            [['/foo/:id?', 'foo', [RequestMethod::METHOD_GET]]],
            '/foo/123',
            ['name' => 'foo', 'substitutions' => ['id' => '123']],
        ];

        yield 'with-optional-param-absent' => [
            [['/foo/:id?', 'foo', [RequestMethod::METHOD_GET]]],
            '/foo',
            ['name' => 'foo'],
        ];
    }
}
