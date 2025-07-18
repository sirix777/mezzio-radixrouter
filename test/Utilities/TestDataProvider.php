<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Utilities;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;

class TestDataProvider
{
    public const COMMON_ROUTES = [
        'users-list' => ['/users', 'GET', 'users.list'],
        'users-get' => ['/users/:id', 'GET', 'users.get'],
        'users-update' => ['/users/:id', 'PUT', 'users.update'],
        'files-wildcard' => ['/files/:path*', 'GET', 'files'],
    ];

    public const REQUEST_SCENARIOS = [
        'simple-match' => ['/users', 'GET', 'users.list'],
        'param-match' => ['/users/123', 'GET', 'users.get', ['id' => '123']],
        'wildcard-match' => ['/files/docs/readme.txt', 'GET', 'files', ['path' => 'docs/readme.txt']],
    ];

    public const ROUTE_PARAMS = [
        'user-id' => ['id' => '123'],
        'user-post' => ['id' => '123', 'postId' => '456'],
        'search' => ['query' => 'test'],
        'wildcard' => ['path' => 'docs/readme.txt'],
    ];

    /**
     * @return array<string, string>
     */
    public static function getHttpMethods(): array
    {
        return [
            'GET' => RequestMethod::METHOD_GET,
            'POST' => RequestMethod::METHOD_POST,
            'PUT' => RequestMethod::METHOD_PUT,
            'DELETE' => RequestMethod::METHOD_DELETE,
            'PATCH' => RequestMethod::METHOD_PATCH,
            'OPTIONS' => RequestMethod::METHOD_OPTIONS,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRouteConfigurations(): array
    {
        return [
            'simple' => [
                'path' => '/users',
                'name' => 'users.list',
                'methods' => [RequestMethod::METHOD_GET],
            ],
            'with-params' => [
                'path' => '/users/:id',
                'name' => 'users.get',
                'methods' => [RequestMethod::METHOD_GET],
            ],
            'with-multiple-methods' => [
                'path' => '/api/data',
                'name' => 'api.data',
                'methods' => [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST],
            ],
            'with-wildcard' => [
                'path' => '/files/:path*',
                'name' => 'files',
                'methods' => [RequestMethod::METHOD_GET],
            ],
        ];
    }
}
