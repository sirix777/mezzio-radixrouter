<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\RadixRouter;

use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Router\RadixRouter;
use Sirix\Mezzio\Router\RadixRouter\ConfigProvider;
use Sirix\Mezzio\Router\RadixRouterFactory;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testProviderReturnsExpectedConfiguration(): void
    {
        $config = ($this->provider)();
        $this->assertArrayHasKey('dependencies', $config);

        $dependencies = $config['dependencies'];
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);

        $factories = $dependencies['factories'];
        $this->assertIsArray($factories);
        $this->assertArrayHasKey(RadixRouter::class, $factories);
        $this->assertSame(RadixRouterFactory::class, $factories[RadixRouter::class]);
    }

    public function testGetDependenciesReturnsExpectedConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);

        $factories = $dependencies['factories'];
        $this->assertArrayHasKey(RadixRouter::class, $factories);
        $this->assertSame(RadixRouterFactory::class, $factories[RadixRouter::class]);
    }
}
