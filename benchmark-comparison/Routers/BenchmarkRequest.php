<?php

declare(strict_types=1);

namespace Sirix\Router\RadixRouter\BenchmarkComparison;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A mutable PSR-7-like request for benchmarking purposes only.
 * Avoids object allocation overhead by allowing path updates.
 */
class BenchmarkRequest implements ServerRequestInterface, UriInterface
{
    private string $path = '/';
    private string $method = 'GET';
    private string $host = 'localhost';

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    // UriInterface methods
    public function getScheme(): string { return 'http'; }
    public function getAuthority(): string { return $this->host; }
    public function getUserInfo(): string { return ''; }
    public function getHost(): string { return $this->host; }
    public function getPort(): ?int { return null; }
    public function getPath(): string { return $this->path; }
    public function getQuery(): string { return ''; }
    public function getFragment(): string { return ''; }
    public function withScheme($scheme): self { return $this; }
    public function withUserInfo($user, $password = null): self { return $this; }
    public function withHost($host): self { return $this; }
    public function withPort($port): self { return $this; }
    public function withPath($path): self { $this->path = $path; return $this; }
    public function withQuery($query): self { return $this; }
    public function withFragment($fragment): self { return $this; }
    public function __toString(): string { return $this->path; }

    // ServerRequestInterface methods
    public function getServerParams(): array { return []; }
    public function getCookieParams(): array { return []; }
    public function withCookieParams(array $cookies): self { return $this; }
    public function getQueryParams(): array { return []; }
    public function withQueryParams(array $query): self { return $this; }
    public function getUploadedFiles(): array { return []; }
    public function withUploadedFiles(array $uploadedFiles): self { return $this; }
    public function getParsedBody() { return null; }
    public function withParsedBody($data): self { return $this; }
    public function getAttributes(): array { return []; }
    public function getAttribute($name, $default = null) { return $default; }
    public function withAttribute($name, $value): self { return $this; }
    public function withoutAttribute($name): self { return $this; }

    // RequestInterface methods
    public function getRequestTarget(): string { return $this->path; }
    public function withRequestTarget($requestTarget): self { return $this; }
    public function getMethod(): string { return $this->method; }
    public function withMethod($method): self { $this->method = $method; return $this; }
    public function getUri(): UriInterface { return $this; }
    public function withUri(UriInterface $uri, $preserveHost = false): self { return $this; }

    // MessageInterface methods
    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion($version): self { return $this; }
    public function getHeaders(): array { return []; }
    public function hasHeader($name): bool { return false; }
    public function getHeader($name): array { return []; }
    public function getHeaderLine($name): string { return ''; }
    public function withHeader($name, $value): self { return $this; }
    public function withAddedHeader($name, $value): self { return $this; }
    public function withoutHeader($name): self { return $this; }
    public function getBody(): StreamInterface { throw new \RuntimeException('Not implemented'); }
    public function withBody(StreamInterface $body): self { return $this; }
}
