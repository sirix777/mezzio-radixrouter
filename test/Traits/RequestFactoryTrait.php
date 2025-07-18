<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Router\Traits;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ServerRequestInterface;

trait RequestFactoryTrait
{
    protected function createServerRequest(string $path, string $method): ServerRequestInterface
    {
        $uri = new Uri($path);

        return new ServerRequest([], [], $uri, $method);
    }

    protected function createGetRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_GET);
    }

    protected function createPostRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_POST);
    }

    protected function createPutRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_PUT);
    }

    protected function createDeleteRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_DELETE);
    }

    protected function createPatchRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_PATCH);
    }

    protected function createOptionsRequest(string $path): ServerRequestInterface
    {
        return $this->createServerRequest($path, RequestMethod::METHOD_OPTIONS);
    }
}
