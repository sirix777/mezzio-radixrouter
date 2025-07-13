<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Router\Enum;

enum CacheConfig: string
{
    case Enabled = 'cache_enabled';
    case File = 'cache_file';
}
