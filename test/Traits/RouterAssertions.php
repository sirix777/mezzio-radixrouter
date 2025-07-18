<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Traits;

use Mezzio\Router\RouteResult;

trait RouterAssertions
{
    /**
     * @param array<string, mixed> $expectedParams
     */
    protected function assertRouteMatches(RouteResult $result, string $expectedRouteName, array $expectedParams = []): void
    {
        $this->assertTrue($result->isSuccess(), 'Route match was not successful');
        $this->assertSame($expectedRouteName, $result->getMatchedRouteName(), 'Route name does not match expected value');
        $this->assertSame($expectedParams, $result->getMatchedParams(), 'Route parameters do not match expected values');
    }

    protected function assertRouteNotFound(RouteResult $result): void
    {
        $this->assertFalse($result->isSuccess(), 'Route should not have matched');
        $this->assertFalse($result->isMethodFailure(), 'Route should not have failed due to method mismatch');
    }

    /**
     * @param array<string> $allowedMethods
     */
    protected function assertMethodNotAllowed(RouteResult $result, array $allowedMethods): void
    {
        $this->assertFalse($result->isSuccess(), 'Route should not have matched');
        $this->assertTrue($result->isMethodFailure(), 'Route should have failed due to method mismatch');
        $this->assertEqualsCanonicalizing($allowedMethods, $result->getAllowedMethods(), 'Allowed methods do not match expected values');
    }

    protected function assertRouteMatchesWithoutParams(RouteResult $result, string $expectedRouteName): void
    {
        $this->assertTrue($result->isSuccess(), 'Route match was not successful');
        $this->assertSame($expectedRouteName, $result->getMatchedRouteName(), 'Route name does not match expected value');
    }

    /**
     * @param array<string, mixed> $expectedParams
     */
    protected function assertRouteMatchesWithParams(RouteResult $result, array $expectedParams): void
    {
        $this->assertTrue($result->isSuccess(), 'Route match was not successful');
        $this->assertSame($expectedParams, $result->getMatchedParams(), 'Route parameters do not match expected values');
    }
}
