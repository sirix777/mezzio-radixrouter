<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Router\RadixRouter;
use SirixTest\Mezzio\Router\Traits\RequestFactoryTrait;
use SirixTest\Mezzio\Router\Traits\RouteDataProviders;
use SirixTest\Mezzio\Router\Traits\RouterAssertions;
use SirixTest\Mezzio\Router\Utilities\RouteAssertionBuilder;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;

/**
 * Base test class for RadixRouter tests.
 */
#[CoversNothing]
class BaseRadixRouterTest extends TestCase
{
    use RequestFactoryTrait;
    use RouterAssertions;
    use RouteDataProviders;

    protected RadixRouter $router;
    protected MiddlewareInterface $middleware;

    protected function setUp(): void
    {
        $this->router = new RadixRouter();
        $this->middleware = $this->createMockMiddleware();
        RouteBuilder::setDefaultMiddleware($this->middleware);
    }

    protected function createMockMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new TextResponse('Hey There!');
            }
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function createRouterWithConfig(array $config): RadixRouter
    {
        return new RadixRouter(config: $config);
    }

    protected function assertRoute(RouteResult $result): RouteAssertionBuilder
    {
        return new RouteAssertionBuilder($result);
    }
}
