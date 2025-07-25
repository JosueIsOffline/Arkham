<?php

namespace JosueIsOffline\Framework\Controllers;

use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\Http\JsonResponse;
use JosueIsOffline\Framework\View\ViewResolver;

abstract class AbstractController
{
  protected ?Request $request = null;
  protected ViewResolver $viewResolver;

  public function __construct()
  {
    $this->viewResolver = new ViewResolver();
  }

  public function render(string $template, ?array $data = []): Response
  {
    $content = $this->viewResolver->render($template, $data);
    $response = new Response($content);
    return $response;
  }

  public function setRequest(Request $request): void
  {
    $this->request = $request;
  }

  public function setViewResolver(ViewResolver $viewResolver): void
  {
    $this->viewResolver = $viewResolver;
  }

  protected function viewExists(string $template): bool
  {
    return $this->viewResolver->viewExists($template);
  }

  protected function renderOrDefault(string $template, string $defaultTemplate, array $data = []): Response
  {
    $templateToRender = $this->viewExists($template) ? $template : $defaultTemplate;
    return $this->render($templateToRender, $data);
  }


  protected function json(array|object $data, int $status = 200): JsonResponse
  {
    return new JsonResponse($data, $status);
  }


  protected function success(array|object $data = [], ?string $message = null, int $status = 200): JsonResponse
  {
    $response = ['success' => true];

    if ($message) {
      $response['message'] = $message;
    }

    if (!empty($data)) {
      $response['data'] = $data;
    }

    return new JsonResponse($response, $status);
  }


  protected function error(string $message, int $status = 400, array $details = []): JsonResponse
  {
    $response = [
      'success' => false,
      'error' => $message
    ];

    if (!empty($details)) {
      $response['details'] = $details;
    }

    return new JsonResponse($response, $status);
  }


  protected function getJsonInput(): array
  {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    return $data ?? [];
  }


  protected function isJsonRequest(): bool
  {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    return strpos($contentType, 'application/json') !== false;
  }


  protected function getQueryParams(): array
  {
    return $_GET;
  }


  protected function getQueryParam(string $name, $default = null)
  {
    return $_GET[$name] ?? $default;
  }
}
