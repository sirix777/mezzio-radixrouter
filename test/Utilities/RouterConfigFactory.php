<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Utilities;

use Sirix\Mezzio\Router\Enum\CacheConfig;

use function sys_get_temp_dir;

class RouterConfigFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function withCache(?string $cacheFile = null): array
    {
        return [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => $cacheFile ?? sys_get_temp_dir() . '/test-cache.php',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function withoutCache(): array
    {
        return [
            CacheConfig::Enabled->value => false,
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public static function withCustomConfig(array $config): array
    {
        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public static function withInvalidCacheDirectory(): array
    {
        return [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => '/root/readonly/cache.php',
        ];
    }
}
