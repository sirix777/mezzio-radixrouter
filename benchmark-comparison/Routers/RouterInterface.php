<?php

declare(strict_types=1);

namespace Sirix\Router\RadixRouter\BenchmarkComparison;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function mount(string $tmpFile): void;

    public function adapt(array $routes): array;

    public function register(array $adaptedRoutes): void;

    public function match(ServerRequestInterface $request): void;

    public static function details(): array;
}
