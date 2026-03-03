# Mezzio Router Benchmark Comparison

This directory contains performance benchmarks comparing Mezzio router adapters: RadixRouter (sirix/mezzio-radixrouter), FastRoute (mezzio/mezzio-fastroute), and LaminasRouter (mezzio/mezzio-laminasrouter).

## Acknowledgments

These benchmarks are **heavily adapted** from the excellent work of **[wilaak/radix-router](https://github.com/wilaak/radix-router)** by [@wilaak](https://github.com/wilaak).

The original radix-router benchmarks provided the foundation for this comparison. Special thanks to [@wilaak](https://github.com/wilaak) for creating such a well-designed benchmark framework and of course the radix-router itself!

## What is Being Tested

This benchmark suite compares six router implementations:

| Router | Description |
|--------|-------------|
| `MezzioRadixRouter` | Radix Tree-based router (sirix/mezzio-radixrouter) |
| `MezzioRadixRouterCached` | Radix Tree-based router with file-based cache |
| `MezzioFastRoute` | Regular expression-based router (mezzio/mezzio-fastroute) |
| `MezzioFastRouteCached` | Regular expression-based router with file-based cache |
| `MezzioLaminasRouter` | Laminas Router (mezzio/mezzio-laminasrouter) |

## Test Suites

The benchmark uses four different route collections:

| Suite | Routes | Description |
|-------|--------|-------------|
| `simple` | 33 | Basic static and parameterized routes |
| `avatax` | 256 | Real-world API routes from Avatax |
| `bitbucket` | 177 | Real-world API routes from Bitbucket |
| `huge` | 500 | Randomly generated complex routes |

## PHP Modes

Each router is tested under three different PHP configurations:

| Mode | Description |
|------|-------------|
| `JIT=tracing` | PHP with JIT compiler (tracing mode) |
| `OPcache` | PHP with OPcache enabled |
| `No OPcache` | PHP without any optimizations |

## How It Works

The benchmark starts multiple PHP built-in servers, each running with a different PHP configuration. For each combination:

1. **Routes are registered** - All routes from the test suite are added to the router
2. **Warmup** - Several warmup requests are made to stabilize JIT/OPcache
3. **Shuffle** - Routes are shuffled before the lookup benchmark to ensure a fair distribution
4. **Benchmark** - Millions of lookups are performed and measured
5. **Memory measurement** - Peak memory usage is recorded

## Running the Benchmarks

### Prerequisites

```bash
cd benchmark-comparison
composer install
```

### Run All Benchmarks

```bash
php bench.php --all
```

### Run Specific Suite

```bash
php bench.php --suite=simple
php bench.php --suite=avatax
php bench.php --suite=bitbucket
php bench.php --suite=huge
```

### Run Specific Mode

```bash
php bench.php --mode="JIT=tracing"
php bench.php --mode=OPcache
php bench.php --mode="No OPcache"
```

### Run Specific Router

```bash
php bench.php --router=MezzioRadixRouterAdapter
php bench.php --router=MezzioRadixRouterCachedAdapter
php bench.php --router=MezzioFastRouteAdapter
php bench.php --router=MezzioFastRouteCachedAdapter
php bench.php --router=MezzioLaminasRouterAdapter
```

### Using Composer Scripts

```bash
composer run bench:mezzio
composer run bench:mezzio-cached
composer run bench:mezzio-all
```

### Combine Options

```bash
# Simple suite with JIT only
php bench.php --suite=simple --mode="JIT=tracing"

# All suites, radix routers only
php bench.php --suite=all --router=MezzioRadixRouterAdapter,MezzioRadixRouterCachedAdapter
```

## Output

The benchmark outputs a formatted table with the following columns:

| Column | Description |
|--------|-------------|
| `Rank` | Performance ranking |
| `Router` | Router implementation name |
| `Mode` | PHP configuration |
| `Lookups/sec` | Number of route lookups per second |
| `Mem (KB)` | Peak memory usage in kilobytes |
| `Register (ms)` | Time to register all routes in milliseconds |

### Metrics explained

- Lookups/sec
  - What it means: Throughput of successful route matches per second (higher is better). Reflects the router's steady‑state lookup speed.
  - How measured: During the Benchmark phase, the harness performs a large number of local HTTP requests against the PHP built‑in server. Each request performs a loop of lookups for a randomized (shuffled) subset of the suite's routes. It measures wall‑clock time for the benchmark window (after warmup) and divides the total number of successful matches by the elapsed time.
  - Scope: The handler does minimal work (creating a PSR-7 Request and calling `match`), but this overhead is present for all routers. Results are sensitive to CPU, JIT/OPcache state, and suite composition.

- Mem (KB)
  - What it means: Peak memory used by the PHP process including routing structures (lower is better).
  - How measured: Calculated as `memory_get_peak_usage(true)` minus a baseline measured before loading any router-specific code or routes. This provides a more accurate representation of the memory overhead introduced by the router and its route collection.
  - Scope: Includes the memory for the router object, internal tree/regex structures, and the route collection itself.

- Register (ms)
  - What it means: Time to prepare the router for the first request (cold start).
  - How measured: High‑resolution timing around the `register()` call and the very first `lookup()` call (since some routers use lazy initialization). To ensure fairness, PSR-7 classes are warmed up before the measurement starts. For cached routers, this primarily measures cache loading and hydration time.
  - Scope: Impacts the "Time to First Byte" on a cold start in environments like CGI or PHP-FPM without persistent processes.

## Benchmarks

> [!NOTE]
> Benchmark results may vary depending on hardware, PHP version, OS configuration, and other environment factors.

> [!NOTE]
> These benchmarks were run inside a virtualization environment on a running machine. This cannot be considered a full test bench as resources are shared among all containers. Results may vary compared to dedicated hardware.

### Environment

- **CPU**: 8 × Intel(R) Core(TM) i7-6700 CPU @ 3.40GHz
- **Memory**: 64Gb
- **PHP**: 8.3.30

#### simple (33 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |       668,375 |       44.9 |           0.128 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |       645,693 |      178.7 |           0.191 |
|    3 | **MezzioRadixRouterCached**  | OPcache            |       515,493 |       43.0 |           0.132 |
|    4 | **MezzioRadixRouter**        | OPcache            |       449,093 |      104.4 |           0.241 |
|    5 | **MezzioRadixRouterCached**  | No OPcache         |       414,167 |      582.5 |           1.440 |
|    6 | **MezzioRadixRouter**        | No OPcache         |       383,283 |      561.2 |           1.286 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |       162,389 |       42.7 |           0.164 |
|    8 | **MezzioFastRouteCached**    | No OPcache         |       131,720 |      587.3 |           2.153 |
|    9 | **MezzioFastRouteCached**    | OPcache            |       118,313 |       40.9 |           0.164 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |        81,157 |      137.6 |           0.310 |
|   11 | **MezzioFastRoute**          | OPcache            |        65,578 |       59.6 |           0.341 |
|   12 | **MezzioFastRoute**          | No OPcache         |        64,843 |      590.6 |           2.324 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |        30,157 |      367.4 |           0.707 |
|   14 | **MezzioLaminasRouter**      | No OPcache         |        25,368 |     1543.5 |           9.524 |
|   15 | **MezzioLaminasRouter**      | OPcache            |        20,493 |      235.8 |           0.850 |

#### avatax (256 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouter**        | JIT=tracing        |       548,877 |      793.2 |           1.281 |
|    2 | **MezzioRadixRouterCached**  | JIT=tracing        |       504,547 |      258.7 |           0.412 |
|    3 | **MezzioRadixRouterCached**  | OPcache            |       418,746 |      258.7 |           0.484 |
|    4 | **MezzioRadixRouter**        | OPcache            |       409,642 |      793.2 |           1.701 |
|    5 | **MezzioRadixRouterCached**  | No OPcache         |       360,036 |     1454.8 |           3.718 |
|    6 | **MezzioRadixRouter**        | No OPcache         |       319,183 |     1272.4 |           2.792 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        31,814 |      236.8 |           0.443 |
|    8 | **MezzioFastRouteCached**    | No OPcache         |        27,736 |      956.6 |           2.986 |
|    9 | **MezzioFastRouteCached**    | OPcache            |        23,841 |      236.8 |           0.497 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |        11,494 |      427.0 |           2.885 |
|   11 | **MezzioFastRoute**          | OPcache            |         9,488 |      421.2 |           3.559 |
|   12 | **MezzioFastRoute**          | No OPcache         |         9,265 |      974.7 |           5.815 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |         5,712 |     1649.6 |           3.005 |
|   14 | **MezzioLaminasRouter**      | OPcache            |         4,534 |     1609.8 |           3.990 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |         4,292 |     2939.5 |          13.165 |

#### bitbucket (177 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouter**        | JIT=tracing        |       472,465 |      633.6 |           1.531 |
|    2 | **MezzioRadixRouterCached**  | JIT=tracing        |       398,965 |      196.2 |           0.353 |
|    3 | **MezzioRadixRouter**        | OPcache            |       361,099 |      633.6 |           1.794 |
|    4 | **MezzioRadixRouterCached**  | OPcache            |       358,660 |      196.2 |           0.373 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       311,773 |     1109.8 |           2.739 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       282,078 |     1259.5 |           3.484 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        38,953 |      175.8 |           0.348 |
|    8 | **MezzioFastRouteCached**    | OPcache            |        33,755 |      175.8 |           0.382 |
|    9 | **MezzioFastRouteCached**    | No OPcache         |        32,031 |      893.0 |           3.533 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |        13,234 |      368.5 |           1.384 |
|   11 | **MezzioFastRoute**          | OPcache            |        10,867 |      367.8 |           1.694 |
|   12 | **MezzioFastRoute**          | No OPcache         |        10,706 |      918.2 |           3.794 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |         7,783 |     1288.6 |           3.284 |
|   14 | **MezzioLaminasRouter**      | OPcache            |         6,422 |     1288.6 |           3.938 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |         6,260 |     2615.1 |          14.084 |

#### huge (500 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |       477,316 |      496.2 |           0.752 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |       408,837 |     2244.5 |           3.080 |
|    3 | **MezzioRadixRouterCached**  | OPcache            |       365,009 |      496.2 |           0.856 |
|    4 | **MezzioRadixRouter**        | OPcache            |       312,791 |     2244.5 |           3.839 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       312,198 |     2737.9 |           4.978 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       305,372 |     3056.3 |           7.030 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        16,193 |      449.5 |           0.884 |
|    8 | **MezzioFastRouteCached**    | OPcache            |        15,054 |      449.4 |           0.948 |
|    9 | **MezzioFastRouteCached**    | No OPcache         |        14,226 |     1455.2 |           4.808 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |         4,256 |      969.1 |           3.582 |
|   11 | **MezzioFastRoute**          | OPcache            |         3,930 |      969.0 |           4.233 |
|   12 | **MezzioFastRoute**          | No OPcache         |         3,851 |     1536.6 |           6.150 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |         3,074 |     3224.2 |           6.834 |
|   14 | **MezzioLaminasRouter**      | OPcache            |         2,136 |     3224.2 |           8.338 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |         1,871 |     4568.0 |          19.315 |

## Performance Summary

### Speed Comparison (No OPcache, simple suite)

| Router | Lookups/sec | vs FastRoute | vs LaminasRouter |
|--------|-------------|--------------|-----------------|
| MezzioRadixRouterCached | ~414,000 | **3x faster** | **16x faster** |
| MezzioRadixRouter | ~383,000 | **3x faster** | **15x faster** |
| MezzioFastRouteCached | ~132,000 | baseline | ~5x faster |
| MezzioFastRoute | ~65,000 | - | ~2.5x faster |
| MezzioLaminasRouter | ~25,000 | - | baseline |

### Key Findings

1. **MezzioRadixRouter is 3-10x faster** than FastRoute in all test scenarios
2. **MezzioRadixRouter is 15-80x faster** than LaminasRouter
3. **Caching significantly improves** RadixRouter and FastRoute performance
4. **JIT provides significant speedup** (up to 3x) for all routers
5. **Memory usage**: Cached versions use significantly less memory

## Notes

> [!IMPORTANT]
> Benchmark results are highly dependent on the environment. Actual performance in your application may vary based on several factors:

- **Hardware**: CPU architecture, clock speed, and available cache significantly impact Radix Tree traversal and Regex execution.
- **PHP Version**: Different PHP versions have varying optimizations in OPcache and JIT compiler.
- **Environment**: These tests use the PHP built-in server for isolation. In production (e.g., PHP-FPM, RoadRunner, or Swoole), results may differ due to process management and communication overhead.
- **Route Complexity**: The "huge" suite uses randomized routes; real-world route patterns in your application might favor one algorithm over another.
- **Variance**: Always use multiple runs to account for JIT warmup and system background tasks.
