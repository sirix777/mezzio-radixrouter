<?php

use Sirix\Mezzio\Router\Enum\CacheConfig;

return [
    'router' => [
        'radix' => [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => 'data/cache/radix-cache.php',
        ],
    ]
];