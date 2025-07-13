<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Router\RadixRouter;

use Mezzio\Router\RouterInterface;
use Sirix\Mezzio\Router\RadixRouter;
use Sirix\Mezzio\Router\RadixRouterFactory;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, string[]>
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                RouterInterface::class => RadixRouter::class,
            ],
            'factories' => [
                RadixRouter::class => RadixRouterFactory::class,
            ],
        ];
    }
}
