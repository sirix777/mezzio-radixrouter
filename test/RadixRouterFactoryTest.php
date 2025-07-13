<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Router\Enum\CacheConfig;
use Sirix\Mezzio\Router\RadixRouter;
use Sirix\Mezzio\Router\RadixRouterFactory;

class RadixRouterFactoryTest extends TestCase
{
    public function testFactoryReturnsRadixRouterWithDefaultConfiguration(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $factory = new RadixRouterFactory();
        $router = $factory($container);

        $this->assertInstanceOf(RadixRouter::class, $router);
    }

    public function testFactoryUsesConfigServiceWhenPresent(): void
    {
        $config = $this->getRouterConfig();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $factory = new RadixRouterFactory();
        $router = $factory($container);

        $this->assertInstanceOf(RadixRouter::class, $router);
    }

    public function testFactoryCanUseArrayObjectConfiguration(): void
    {
        $config = $this->getRouterConfig(false);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $factory = new RadixRouterFactory();
        $router = $factory($container);

        $this->assertInstanceOf(RadixRouter::class, $router);
    }

    /**
     * @return array{
     *     router: array{
     *         radix: array{
     *             cache_enabled: bool,
     *             cache_file: string
     *         }
     *     }
     * }
     */
    public function getRouterConfig(bool $cacheEnabled = true): array
    {
        return [
            'router' => [
                'radix' => [
                    CacheConfig::Enabled->value => $cacheEnabled,
                    CacheConfig::File->value => __DIR__ . '/radix-router.cache.php',
                ],
            ],
        ];
    }
}
