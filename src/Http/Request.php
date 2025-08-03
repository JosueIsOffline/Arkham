<?php

namespace JosueIsOffline\Framework\Http;

class Request
{
  private static $instance = null;

  private function __construct(
    private array $server,
    private array $get,
    private array $post,
    private array $files,
    private array $cookie,
    private array $env,
  ) {}

  public static function create(): static
  {
    if (null === static::$instance) {
      static::$instance = new static(
        $_SERVER,
        $_GET,
        $_POST,
        $_FILES,
        $_COOKIE,
        $_ENV,
      );
    }

    return static::$instance;
  }

  public function getMethod(): string
  {
    return $this->server['REQUEST_METHOD'];
  }

  public function getUri(): string
  {
    return $this->server['REQUEST_URI'];
  }

  public function getPathInfo(): string
  {
    $uri = $this->getUri();

    // Remove query string if present
    if (($pos = strpos($uri, '?')) !== false) {
      $uri = substr($uri, 0, $pos);
    }

    // Remove fragment if present
    if (($pos = strpos($uri, '#')) !== false) {
      $uri = substr($uri, 0, $pos);
    }

    // Ensure it starts with /
    if (!str_starts_with($uri, '/')) {
      $uri = '/' . $uri;
    }

    return $uri;
  }

  public function getQueryString(): ?string
  {
    $uri = $this->getUri();
    if (($pos = strpos($uri, '?')) !== false) {
      return substr($uri, $pos + 1);
    }
    return null;
  }

  public function getPostParams(string $name): string
  {
    return $this->post[$name];
  }

  public function getAllPost(): array
  {
    return $this->post;
  }

  public function getQueryParams(): array
  {
    return $this->get;
  }

  public function getQueryParam(string $name, $default = null)
  {
    return $this->get[$name] ?? $default;
  }
}
