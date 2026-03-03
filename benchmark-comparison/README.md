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
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |     1,279,430 |       45.1 |           0.073 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |     1,256,213 |      178.7 |           0.149 |
|    3 | **MezzioRadixRouter**        | OPcache            |       888,638 |      104.4 |           0.131 |
|    4 | **MezzioRadixRouterCached**  | OPcache            |       885,149 |       43.2 |           0.081 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       761,096 |      558.2 |           0.800 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       754,229 |      579.5 |           0.923 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |       294,001 |       42.9 |           0.111 |
|    8 | **MezzioFastRouteCached**    | OPcache            |       230,862 |       41.1 |           0.119 |
|    9 | **MezzioFastRouteCached**    | No OPcache         |       221,530 |      583.9 |           1.516 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |       137,812 |      137.6 |           0.194 |
|   11 | **MezzioFastRoute**          | OPcache            |       104,908 |       59.6 |           0.220 |
|   12 | **MezzioFastRoute**          | No OPcache         |       103,747 |      587.2 |           1.688 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |        56,560 |      371.0 |           0.483 |
|   14 | **MezzioLaminasRouter**      | OPcache            |        40,774 |      222.8 |           0.550 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |        40,195 |     1532.4 |           6.168 |

#### avatax (256 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |       924,252 |      258.6 |           0.268 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |       889,857 |      793.2 |           0.731 |
|    3 | **MezzioRadixRouter**        | OPcache            |       671,473 |      793.2 |           1.071 |
|    4 | **MezzioRadixRouterCached**  | OPcache            |       646,639 |      258.6 |           0.334 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       623,333 |     1269.4 |           1.650 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       563,701 |     1451.8 |           2.354 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        52,771 |      236.8 |           0.457 |
|    8 | **MezzioFastRouteCached**    | OPcache            |        45,111 |      236.7 |           0.319 |
|    9 | **MezzioFastRouteCached**    | No OPcache         |        43,311 |      953.1 |           2.326 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |        20,571 |      427.0 |           1.768 |
|   11 | **MezzioFastRoute**          | OPcache            |        16,300 |      421.2 |           2.120 |
|   12 | **MezzioFastRoute**          | No OPcache         |        15,433 |      971.3 |           3.605 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |         9,741 |     1627.9 |           2.002 |
|   14 | **MezzioLaminasRouter**      | OPcache            |         7,635 |     1596.8 |           2.635 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |         7,425 |     2928.5 |           8.540 |

#### bitbucket (177 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |       824,823 |      196.2 |           0.194 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |       766,894 |      633.6 |           0.730 |
|    3 | **MezzioRadixRouterCached**  | OPcache            |       597,144 |      196.2 |           0.203 |
|    4 | **MezzioRadixRouter**        | OPcache            |       589,009 |      633.6 |           0.871 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       524,533 |     1106.7 |           1.526 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       514,334 |     1256.5 |           2.091 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        63,578 |      175.7 |           0.221 |
|    8 | **MezzioFastRouteCached**    | No OPcache         |        52,280 |      889.5 |           1.909 |
|    9 | **MezzioFastRouteCached**    | OPcache            |        49,705 |      175.7 |           0.272 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |        20,344 |      368.5 |           0.783 |
|   11 | **MezzioFastRoute**          | No OPcache         |        16,401 |      914.8 |           2.493 |
|   12 | **MezzioFastRoute**          | OPcache            |        16,167 |      367.8 |           1.121 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |        13,024 |     1276.0 |           1.528 |
|   14 | **MezzioLaminasRouter**      | OPcache            |        10,422 |     1275.5 |           2.518 |
|   15 | **MezzioLaminasRouter**      | No OPcache         |         9,379 |     2604.1 |           8.419 |

#### huge (500 routes)

| Rank | Router                       | Mode               | Lookups/sec   | Mem (KB)   | Register (ms)   |
|------|------------------------------|--------------------|---------------|------------|-----------------|
|    1 | **MezzioRadixRouterCached**  | JIT=tracing        |       826,967 |      496.1 |           0.468 |
|    2 | **MezzioRadixRouter**        | JIT=tracing        |       774,455 |     2244.5 |           1.626 |
|    3 | **MezzioRadixRouterCached**  | OPcache            |       613,734 |      496.1 |           0.506 |
|    4 | **MezzioRadixRouter**        | OPcache            |       592,844 |     2244.5 |           2.086 |
|    5 | **MezzioRadixRouter**        | No OPcache         |       539,251 |     2734.9 |           2.970 |
|    6 | **MezzioRadixRouterCached**  | No OPcache         |       481,211 |     3053.3 |           4.739 |
|    7 | **MezzioFastRouteCached**    | JIT=tracing        |        24,424 |      449.4 |           0.411 |
|    8 | **MezzioFastRouteCached**    | OPcache            |        23,511 |      449.4 |           0.480 |
|    9 | **MezzioFastRouteCached**    | No OPcache         |        21,829 |     1451.8 |           2.610 |
|   10 | **MezzioFastRoute**          | JIT=tracing        |         7,132 |      969.1 |           1.355 |
|   11 | **MezzioFastRoute**          | OPcache            |         6,048 |      969.0 |           1.814 |
|   12 | **MezzioFastRoute**          | No OPcache         |         5,798 |     1533.2 |           3.384 |
|   13 | **MezzioLaminasRouter**      | JIT=tracing        |         5,042 |     3211.2 |           3.706 |
|   14 | **MezzioLaminasRouter**      | No OPcache         |         3,944 |     4556.9 |          11.058 |
|   15 | **MezzioLaminasRouter**      | OPcache            |         3,840 |     3211.2 |           5.156 |

## Performance Summary

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
