# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 30/08/2025

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

## [2.0.0] - 19/07/2025

### Changed
- Upgraded to wilaak/radix-router v2.1
- Completely refactored test suite with more comprehensive test cases
- Improved test organization with separate test classes for different router features
- Added utility classes and traits for better test maintainability

### Added
- New test cases for basic routing, parameters, wildcards, URI generation, and error handling
- Support for more complex routing scenarios

## [1.0.0] - 13/07/2025

### Added
- Initial release of RadixRouter integration for Mezzio
- High-performance routing using a radix tree algorithm
- PSR-7 and PSR-15 compatibility
- Support for route parameters and optional parameters
- Route caching for improved performance
- Full compatibility with Mezzio middleware architecture
- ConfigProvider for automatic configuration
- Support for PHP 8.1, 8.2, 8.3, and 8.4
