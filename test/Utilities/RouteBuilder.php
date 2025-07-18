<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Utilities;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Route;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

class RouteBuilder
{
    private static ?MiddlewareInterface $defaultMiddleware = null;

    public static function setDefaultMiddleware(MiddlewareInterface $middleware): void
    {
        self::$defaultMiddleware = $middleware;
    }

    /**
     * @param non-empty-string $path
     */
    public static function simple(string $path, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_GET], $name);
    }

    /**
     * @param list<string>     $methods
     * @param non-empty-string $path
     */
    public static function withMethods(string $path, array $methods, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), $methods, $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function withParams(string $path, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_GET], $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function wildcard(string $path, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_GET], $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function post(string $path, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_POST], $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function put(string $path, ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_PUT], $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function delete(string $path = '/', ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), [RequestMethod::METHOD_DELETE], $name);
    }

    /**
     * @param non-empty-string $path
     */
    public static function any(string $path = '/', ?string $name = null): Route
    {
        return new Route($path, self::getMiddleware(), null, $name);
    }

    private static function getMiddleware(): MiddlewareInterface
    {
        if (! self::$defaultMiddleware instanceof MiddlewareInterface) {
            throw new RuntimeException('Default middleware not set. Call RouteBuilder::setDefaultMiddleware() first.');
        }

        return self::$defaultMiddleware;
    }
}
