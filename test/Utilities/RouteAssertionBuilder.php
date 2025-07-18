<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Utilities;

use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Assert;

class RouteAssertionBuilder
{
    public function __construct(private readonly RouteResult $result) {}

    public function shouldSucceed(): self
    {
        Assert::assertTrue($this->result->isSuccess(), 'Route match was not successful');

        return $this;
    }

    public function shouldFail(): self
    {
        Assert::assertFalse($this->result->isSuccess(), 'Route should not have matched');

        return $this;
    }

    public function withRouteName(string $name): self
    {
        Assert::assertSame($name, $this->result->getMatchedRouteName(), 'Route name does not match expected value');

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function withParams(array $params): self
    {
        Assert::assertSame($params, $this->result->getMatchedParams(), 'Route parameters do not match expected values');

        return $this;
    }

    public function withMethodFailure(): self
    {
        Assert::assertTrue($this->result->isMethodFailure(), 'Route should have failed due to method mismatch');

        return $this;
    }

    public function withoutMethodFailure(): self
    {
        Assert::assertFalse($this->result->isMethodFailure(), 'Route should not have failed due to method mismatch');

        return $this;
    }

    /**
     * @param array<string> $methods
     */
    public function withAllowedMethods(array $methods): self
    {
        Assert::assertEqualsCanonicalizing($methods, $this->result->getAllowedMethods(), 'Allowed methods do not match expected values');

        return $this;
    }

    public function withMatchedRoute(string $expectedRouteName): self
    {
        $matchedRoute = $this->result->getMatchedRoute();
        Assert::assertNotFalse($matchedRoute, 'No route was matched');
        Assert::assertSame($expectedRouteName, $matchedRoute->getName(), 'Matched route name does not match expected value');

        return $this;
    }
}
