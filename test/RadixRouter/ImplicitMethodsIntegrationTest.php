<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Generator;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Test\AbstractImplicitMethodsIntegrationTest;
use Sirix\Mezzio\Router\RadixRouter;

class ImplicitMethodsIntegrationTest extends AbstractImplicitMethodsIntegrationTest
{
    public function getRouter(): RouterInterface
    {
        return new RadixRouter();
    }

    public static function implicitRoutesAndRequests(): Generator
    {
        yield 'static path' => ['/api/v1/me', [], '/api/v1/me', []];

        $params = [
            'id' => '3',
        ];

        yield 'path with parameter' => ['/api/v1/:id', [], '/api/v1/3', $params];
    }
}
