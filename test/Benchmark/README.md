# RadixRouter Benchmarks

This directory contains benchmark tests for the RadixRouter component using [PHPBench](https://github.com/phpbench/phpbench).

## Running the Benchmarks

To run all benchmarks:

```bash
composer bench
```

This will execute all benchmark tests and display the results in an aggregate report.

## Available Benchmarks

### RadixRouterMatchBench

Benchmarks for the route matching functionality:

- `benchMatchSimpleRoute`: Matching a simple route
- `benchMatchRouteWithParameters`: Matching a route with parameters
- `benchMatchRouteWithMultipleParameters`: Matching a route with multiple parameters
- `benchMatchRouteWithOptionalParameters`: Matching a route with optional parameters
- `benchMatchRouteWithMultipleHttpMethods`: Matching a route with multiple HTTP methods
- `benchMatchNonExistentRoute`: Matching a non-existent route

### RadixRouterMatchCachedBench

Benchmarks for the route matching functionality with caching enabled:

- `benchMatchSimpleRoute`: Matching a simple route with caching
- `benchMatchRouteWithParameters`: Matching a route with parameters with caching
- `benchMatchRouteWithMultipleParameters`: Matching a route with multiple parameters with caching
- `benchMatchRouteWithOptionalParameters`: Matching a route with optional parameters with caching
- `benchMatchRouteWithMultipleHttpMethods`: Matching a route with multiple HTTP methods with caching
- `benchMatchNonExistentRoute`: Matching a non-existent route with caching

### RadixRouterGenerateUriBench

Benchmarks for the URI generation functionality:

- `benchGenerateUriSimpleRoute`: Generating a URI for a simple route
- `benchGenerateUriWithSingleParameter`: Generating a URI with a single parameter
- `benchGenerateUriWithMultipleParameters`: Generating a URI with multiple parameters
- `benchGenerateUriWithOptionalParameterProvided`: Generating a URI with an optional parameter that is provided
- `benchGenerateUriWithOptionalParameterOmitted`: Generating a URI with an optional parameter that is omitted
- `benchGenerateUriWithMultipleOptionalParameters`: Generating a URI with multiple optional parameters
- `benchGenerateUriWithSomeOptionalParametersOmitted`: Generating a URI with some optional parameters omitted

### RadixRouterGenerateUriCachedBench

Benchmarks for the URI generation functionality with caching enabled:

- `benchGenerateUriSimpleRoute`: Generating a URI for a simple route with caching
- `benchGenerateUriWithSingleParameter`: Generating a URI with a single parameter with caching
- `benchGenerateUriWithMultipleParameters`: Generating a URI with multiple parameters with caching
- `benchGenerateUriWithOptionalParameterProvided`: Generating a URI with an optional parameter that is provided with caching
- `benchGenerateUriWithOptionalParameterOmitted`: Generating a URI with an optional parameter that is omitted with caching
- `benchGenerateUriWithMultipleOptionalParameters`: Generating a URI with multiple optional parameters with caching
- `benchGenerateUriWithSomeOptionalParametersOmitted`: Generating a URI with some optional parameters omitted with caching

### RadixRouterAddRouteBench

Benchmarks for the route addition functionality:

- `benchAddSimpleRoute`: Adding a simple route
- `benchAddRouteWithParameters`: Adding a route with parameters
- `benchAddRouteWithMultipleParameters`: Adding a route with multiple parameters
- `benchAddRouteWithOptionalParameters`: Adding a route with optional parameters
- `benchAddRouteWithMultipleOptionalParameters`: Adding a route with multiple optional parameters
- `benchAddRouteWithMultipleHttpMethods`: Adding a route with multiple HTTP methods
- `benchAddMultipleRoutes`: Adding multiple routes
- `benchAddRoutesWithSamePathDifferentMethods`: Adding routes with the same path but different methods

### RadixRouterAddRouteCachedBench

Benchmarks for the route addition functionality with caching enabled:

- `benchAddSimpleRoute`: Adding a simple route with caching
- `benchAddRouteWithParameters`: Adding a route with parameters with caching
- `benchAddRouteWithMultipleParameters`: Adding a route with multiple parameters with caching
- `benchAddRouteWithOptionalParameters`: Adding a route with optional parameters with caching
- `benchAddRouteWithMultipleOptionalParameters`: Adding a route with multiple optional parameters with caching
- `benchAddRouteWithMultipleHttpMethods`: Adding a route with multiple HTTP methods with caching
- `benchAddMultipleRoutes`: Adding multiple routes with caching
- `benchAddRoutesWithSamePathDifferentMethods`: Adding routes with the same path but different methods with caching

## Custom Reports

You can run specific benchmarks or use custom reports by using the phpbench command directly:

```bash
vendor/bin/phpbench run test/Benchmark --report=default
vendor/bin/phpbench run test/Benchmark/RadixRouterMatchBench.php --report=default
```

## Configuration

The PHPBench configuration is stored in `phpbench.json` in the project root.
