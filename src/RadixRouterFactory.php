<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Router;

use ArrayAccess;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function assert;
use function is_array;

/**
 * Create and return an instance of RadixRouter.
 *
 * Configuration should look like the following:
 *
 * <code>
 * 'router' => [
 *     'radix' => [
 *         CacheConfig::Enabled->value => true, // true|false
 *         CacheConfig::File->value    => '(/absolute/)path/to/cache/file', // optional
 *     ],
 * ]
 * </code>
 *
 * @phpstan-import-type RadixRouteConfig from RadixRouter
 */
final class RadixRouterFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RadixRouter
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        assert(is_array($config) || $config instanceof ArrayAccess);
        $routerConfig = $config['router'] ?? [];
        assert(is_array($routerConfig) || $routerConfig instanceof ArrayAccess);
        $options = $routerConfig['radix'] ?? [];
        assert(is_array($options));

        return new RadixRouter(config: $options);
    }
}
