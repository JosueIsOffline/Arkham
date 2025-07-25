<?php

namespace JosueIsOffline\Framework\Http;

class JsonResponse extends Response
{
  public function __construct(
    array|object $data,
    int $status = 200,
    array $headers = []
  ) {
    $headers['Content-Type'] = 'application/json';

    $jsonContent = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($jsonContent === false) {
      $jsonContent = json_encode(['error' => 'Error encoding JSON data']);
      $status = 500;
    }

    parent::__construct($jsonContent, $status, $headers);
  }

  public function send(): void
  {
    foreach ($this->headers as $name => $value) {
      header("$name: $value");
    }

    echo $this->content;
  }
}
