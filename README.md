# Mezzio RadixRouter

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

RadixRouter integration for [Mezzio](https://docs.mezzio.dev/), providing high-performance HTTP routing using a radix tree algorithm.

## Installation

Install this package via Composer:

```bash
composer require sirix/mezzio-radixrouter
```

## Features

- High-performance routing using a radix tree algorithm
- PSR-7 and PSR-15 compatible
- Supports route parameters and optional parameters
- Route caching for improved performance
- Fully compatible with Mezzio middleware architecture

## Usage

### Basic Setup

There are two ways to set up RadixRouter in your Mezzio application:

#### Automatic Configuration (Recommended)

The easiest way to set up RadixRouter is to use the provided ConfigProvider, which automatically registers all necessary dependencies:

```php
// In config/config.php or similar configuration file
$aggregator = new ConfigAggregator([
    // ... other config providers
    \Sirix\Mezzio\Router\RadixRouter\ConfigProvider::class,
    // ... other config providers
]);
```

This will automatically register the RadixRouter as the default router implementation for your application.

#### Manual Configuration

Alternatively, you can manually update your dependencies configuration:

```php
// In config/autoload/dependencies.php or similar configuration file
use Mezzio\Router\RouterInterface;
use Sirix\Mezzio\Router\RadixRouter;
use Sirix\Mezzio\Router\RadixRouterFactory;

return [
    'dependencies' => [
        'factories' => [
            RouterInterface::class => RadixRouterFactory::class,
        ],
    ],
];
```

### Route Configuration

Routes can be defined in your Mezzio application as usual:

```php
// In config/routes.php or similar
$app->get('/api/users', [UserListHandler::class], 'api.users');
$app->get('/api/users/:id', [UserDetailsHandler::class], 'api.user');
$app->post('/api/users', [CreateUserHandler::class]);
```

### Caching Configuration

To enable route caching for improved performance:

```php
// In config/autoload/router.global.php or similar
use Sirix\Mezzio\Router\Enum\CacheConfig;

return [
    'router' => [
        'radix' => [
            CacheConfig::Enabled->value => true,
            CacheConfig::File->value => 'data/cache/radix-cache.php',
        ],
    ]
];
```

## Documentation

For more information about routing in Mezzio, please refer to the [Mezzio routing documentation](https://docs.mezzio.dev/mezzio/v3/features/router/intro/).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- [Sirix](https://github.com/sirix777) - Project maintainer
- [Wilaak RadixRouter](https://github.com/Wilaak/RadixRouter) - The underlying radix tree router implementation
- [Mezzio](https://github.com/mezzio/mezzio) - The middleware framework
