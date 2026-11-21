# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.1.0] - 2026-11-21

### Changed
- Supported PHP window is now 8.3–8.6 (8.6 added, 8.2 dropped)

## [4.0.0] - 2026-11-16

### Changed
- **BREAKING:** a `HEAD` request to a route that only allows `GET` is now a method failure, as in `mezzio-fastroute` and `mezzio-laminasrouter`. `Mezzio\Router\Middleware\ImplicitHeadMiddleware` then dispatches it as `GET` and empties the response body. Applications without that middleware in their pipeline will answer such requests with `405`; add the middleware or declare `HEAD` on the route.
- Delete the route cache file after upgrading: a file written by 3.2.x keeps `HEAD` in the allowed methods until it is regenerated.
- `wilaak/radix-router` minimum raised to `^3.7.1`

### Fixed
- The allowed methods of a method failure (and the `Allow` header built from them) no longer contain a `HEAD` that no route declares.
- Registering separate `GET` and `HEAD` routes for the same path no longer throws `Route Conflict`.

### Removed
- Automatic `HEAD` injection for routes allowing `GET` (added in 3.2.0).

## [3.2.3] - 2026-09-08

### Fixed
- Route default parameters configured with `Route::setOptions()` are now included in matched route parameters; values captured from the request path take precedence over defaults.

## [3.2.2] - 2026-08-03

### Added
- Support for `mezzio/mezzio-router` 3.20+ alongside 4.x
- CI compatibility matrix for Mezzio Router 3.20 and 4.x on PHP 8.2 and 8.5

## [3.2.1] - 2026-03-03

### Added
- Comprehensive benchmark suite comparing RadixRouter with FastRoute and LaminasRouter (in `benchmark-comparison/`)
- Benchmark results and documentation section in main `README.md`

## [3.2.0] - 2026-01-07

### Added
- Automatic HEAD method injection for routes allowing GET

## [3.1.0] - 2025-11-22

### Added
- Official support for PHP 8.5 (composer `php` constraint updated)
- Composer tooling: composer-normalize configuration and composer-dependency-analyser setup

### Changed
- Dependencies updated:
    - `wilaak/radix-router` to `^3.5`
    - `mezzio/mezzio-router` to `^4.1`
- CI workflow improvements in `.github/workflows/php.yml`
- Renamed config file from `config/autoload/radix-routes.global.php` to `config/autoload/radix-router.global.php` for consistency

### Fixed
- Test suite updates and refinements (providers and basic/URI generation tests) to align with dependency changes


## [3.0.0] - 2025-08-30

### Changed
- Upgrade to wilaak/radix-router v3.x bringing performance improvements and API stability
- Router implementation refined: safer cache handling with error suppression when reading/writing cache files
- Normalized URI generation: improved optional and wildcard parameter handling and path normalization
- Test utilities consolidated via RouteBuilder (centralized factory methods) and default middleware requirement
- Documentation updates: expanded README with setup options and caching configuration

### Added
- Support for Mezzio 4 router contracts (mezzio/mezzio-router ^4.0.1)
- New optional/wildcard route patterns documentation and examples

### Fixed
- Properly preserve router cached dispatch data and avoid duplicate injection when cache is present
- More robust cache directory creation and writability checks with clear exceptions

## [2.0.0] - 2025-07-19

### Changed
- Upgraded to wilaak/radix-router v2.1
- Completely refactored test suite with more comprehensive test cases
- Improved test organization with separate test classes for different router features
- Added utility classes and traits for better test maintainability

### Added
- New test cases for basic routing, parameters, wildcards, URI generation, and error handling
- Support for more complex routing scenarios

## [1.0.0] - 2025-07-13

### Added
- Initial release of RadixRouter integration for Mezzio
- High-performance routing using a radix tree algorithm
- PSR-7 and PSR-15 compatibility
- Support for route parameters and optional parameters
- Route caching for improved performance
- Full compatibility with Mezzio middleware architecture
- ConfigProvider for automatic configuration
- Support for PHP 8.1, 8.2, 8.3, and 8.4
