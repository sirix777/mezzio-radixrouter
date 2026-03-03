<?php

use Nyholm\Psr7\ServerRequest;
use Sirix\Router\RadixRouter\BenchmarkComparison\BenchmarkRequest;

$suite = $_GET["suite"] ?? null;
$routerClass = $_GET["router"] ?? null;
$iterations = isset($_GET["iterations"]) ? (int) $_GET["iterations"] : null;
$benchmarkDuration = isset($_GET["duration"]) ? (float) $_GET["duration"] : null;

if (!$suite || !$routerClass) {
    header("Content-Type: application/json; charset=utf-8");
    $reason = null;
    if (!$suite) {
        $reason = "Missing suite parameter.";
    } elseif (!$routerClass) {
        $reason = "Missing router parameter.";
    }
    echo json_encode([
        "error" => "Invalid parameters",
        "reason" => $reason,
        "usage" => "?suite={suite_name}&router={router_class}&[iterations=N]&[duration=SECONDS]",
    ]);
    exit(1);
}

header("Content-Type: application/json");

$memoryBaseline = memory_get_usage();

require __DIR__ . "/../vendor/autoload.php";

$router = new $routerClass();
$routerName = $routerClass::details()["name"];

$routes = require __DIR__ . "/../routes/$suite.php";

if (!is_dir(__DIR__ . "/../cache")) {
    mkdir(__DIR__ . "/../cache", 0777, true);
}

$router->mount(__DIR__ . "/../cache/{$routerName}_$suite");

$adaptedRoutes = $router->adapt($routes);
$routeCount = count($routes);
$shuffledRoutes = $routes;
shuffle($shuffledRoutes);

// Warmup autoload and structures before starting registration measurement
$testRequest = new ServerRequest('GET', '/');

$registerStart = \microtime(true);
$router->register($adaptedRoutes);
// Include the first lookup (lazy init/build/restore)
$benchRequest = new BenchmarkRequest();
$benchRequest->setPath($routes[0]);
$router->match($benchRequest);
$registerEnd = \microtime(true);

$registerTimeMs = ($registerEnd - $registerStart) * 1000;

memory_reset_peak_usage();

$totalIterations = 0;
$duration = 0.0;

if ($iterations !== null && $iterations > 0) {
    $start = \microtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $index = $i % $routeCount;
        $benchRequest->setPath($shuffledRoutes[$index]);
        $router->match($benchRequest);
    }
    $end = \microtime(true);

    $duration = $end - $start;
    $totalIterations = $iterations;
} else {
    $benchmarkDuration = $benchmarkDuration > 0 ? $benchmarkDuration : 1.0;
    $batchSize = 10_000;

    $start = \microtime(true);

    $end = $start;
    while ($end - $start < $benchmarkDuration) {
        for ($i = 0; $i < $batchSize; $i++) {
            $index = ($totalIterations + $i) % $routeCount;
            $benchRequest->setPath($shuffledRoutes[$index]);
            $router->match($benchRequest);
        }
        $totalIterations += $batchSize;

        $end = \microtime(true);
    }

    $duration = $end - $start;
}

$lookupsPerSecond = $duration > 0 ? $totalIterations / $duration : 0;

$actualPeakMemory = memory_get_peak_usage() - $memoryBaseline;
$actualMemory = memory_get_usage() - $memoryBaseline;

$result = [
    "router" => $routerName,
    "router_class" => $routerClass,
    "suite" => $suite,
    "lookups_per_second" => $lookupsPerSecond,
    "total_iterations" => $totalIterations,
    "duration_seconds" => $duration,
    "peak_memory_kb" => $actualPeakMemory / 1024,
    "memory_kb" => $actualMemory / 1024,
    "register_time_ms" => $registerTimeMs,
];

echo json_encode($result);
exit(0);
