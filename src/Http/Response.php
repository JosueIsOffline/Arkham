<?php

namespace JosueIsOffline\Framework\Http;

class Response
{
  public function __construct(
    protected ?string $content = '',
    protected int $status = 200,
    protected array $headers = [],
  ) {
    http_response_code($status);
  }

  public function send(): void
  {
    foreach ($this->headers as $name => $value) {
      header("$name: $value");
    }

    echo $this->content;
  }

  public function getContent(): ?string
  {
    return $this->content;
  }

  public function getStatus(): int
  {
    return $this->status;
  }

  public function getHeaders(): array
  {
    return $this->headers;
  }
}
