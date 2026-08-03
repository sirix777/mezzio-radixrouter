<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use Mezzio\Router\RouterInterface;
use SirixTest\Mezzio\Router\Utilities\RouteBuilder;

class MezzioRouterContractTest extends BaseRadixRouterTest
{
    public function testImplementsRouterInterfaceAndDefersRouteInjection(): void
    {
        $this->assertInstanceOf(RouterInterface::class, $this->router);

        $route = RouteBuilder::simple('/contract', 'before-injection');
        $this->router->addRoute($route);
        $route->setName('after-injection');

        $result = $this->router->match($this->createGetRequest('/contract'));

        $this->assertRouteMatches($result, 'after-injection');
        $this->assertSame($route, $result->getMatchedRoute());
    }
}
