<?php

namespace JosueIsOffline\Framework\Controllers;

use JosueIsOffline\Framework\Auth\AuthService;
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
    return new Response($content);
  }

  public function renderWithFlash(string $template, array $data = []): Response
  {
    $flashData = $this->getFlashData();
    $mergedData = array_merge($data, $flashData);
    return $this->render($template, $mergedData);
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

  protected function success(array|object $data = [], ?string $message = null, int $status = 200, ?string $redirectTo = '/'): JsonResponse|Response
  {
    $response = ['success' => true];

    if ($message) {
      $response['message'] = $message;
    }

    if (!empty($data)) {
      $response['data'] = $data;
    }

    return $this->isAjaxOrApiRequest()
      ? new JsonResponse($response, $status)
      : $this->smartResponse($response, $status, $redirectTo);
  }

  protected function isFormSubmission(): bool
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    return $method === 'POST' &&
      strpos($contentType, 'application/x-www-form-urlencoded') !== false;
  }

  protected function smartResponse(array $data = [],  int $status = 200, string $redirectUrl = '/',): Response|JsonResponse
  {
    if ($this->isFormSubmission()) {
      $this->setFlashData($data);
      return $this->redirect($redirectUrl);
    }

    if ($this->isAjaxOrApiRequest()) {
      return new JsonResponse($data, $status);
    }

    $this->setFlashData($data);
    return $this->redirect($redirectUrl);
  }

  protected function error(array $details = [], string $message, int $status = 400, ?string $redirectTo = null): JsonResponse|Response
  {
    $response = [
      'success' => false,
      'error' => [
        'message' => $message,
      ]
    ];

    if (!empty($details)) {
      $response['error']['details'] = $details;
    }

    if ($this->isFormSubmission()) {
      $this->setFlashData($response);
      $redirectUrl = $redirectTo ?? $_SERVER['HTTP_REFERER'] ?? '/';
      return $this->redirect($redirectUrl);
    }

    if ($this->isAjaxOrApiRequest()) {
      return new JsonResponse($response, $status);
    }

    $this->setFlashData($response);
    $redirectUrl = $redirectTo ?? $this->getRefererUrl() ?? '/';
    return $this->redirect($redirectUrl);
  }

  protected function isAjaxOrApiRequest(): bool
  {
    if (
      isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
      return true;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
      return true;
    }

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (
      strpos($accept, 'application/json') !== false &&
      strpos($accept, 'text/html') === false
    ) {
      return true;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') !== false) {
      return true;
    }

    return false;
  }

  protected function redirect(string $url, int $status = 302): Response
  {
    return new Response('', $status, ['Location' => $url]);
  }

  protected function back(): Response
  {
    return $this->redirect($this->getRefererUrl());
  }

  private function getRefererUrl(): string
  {
    return $_SERVER['HTTP_REFERER'] ?? '/';
  }

  protected function setFlashData(array $data): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION['_flash_data'] = $data;
  }

  protected function getFlashData(): array
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $data = $_SESSION['_flash_data'] ?? [];
    unset($_SESSION['_flash_data']);

    return $data;
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

  // === VALIDATION HELPERS ===

  protected function validateRequired(array $data, array $required): array
  {
    $errors = [];
    foreach ($required as $field) {
      if (!isset($data[$field]) || empty($data[$field])) {
        $errors[$field] = ucfirst($field) . ' is required';
      }
    }
    return $errors;
  }

  protected function validateEmail(string $email): bool
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  // === SECURITY HELPERS ===

  protected function requireAuth(): void
  {
    if (!$this->auth()->user()) {
      if ($this->isAjaxOrApiRequest()) {
        throw new \Exception('Authentication required', 401);
      }
      $this->setFlashData(['error' => 'You must be logged in']);
      header('Location: /login');
      exit;
    }
  }

  protected function requireRole(string $role): void
  {
    $this->requireAuth();
    if (!$this->hasRole($role)) {
      if ($this->isAjaxOrApiRequest()) {
        throw new \Exception('Insufficient permissions', 403);
      }
      $this->setFlashData(['error' => 'Access denied']);
      header('Location: /');
      exit;
    }
  }

  protected function auth(): \JosueIsOffline\Framework\Auth\AuthService
  {
    return new \JosueIsOffline\Framework\Auth\AuthService();
  }

  protected function user(): ?array
  {
    return $this->auth()->user();
  }

  protected function userId(): ?int
  {
    return $this->auth()->id();
  }

  protected function userRole(): ?array
  {
    return $this->auth()->role();
  }

  protected function hasRole(string $role): bool
  {
    return $this->auth()->hasRole($role);
  }

  protected function hasPermission(string $permission): bool
  {
    return $this->auth()->hasPermission($permission);
  }
}
