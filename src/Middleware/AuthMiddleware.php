<?php

namespace JosueIsOffline\Framework\Middleware;

use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\Auth\AuthService;

class AuthMiddleware
{
  public function handle(Request $request, callable $next): Response
  {
    $auth = new AuthService();
    if (!$auth->check()) {
      return new Response('', 302, ['Location' => '/login']);
    }
    return $next($request);
  }
}

class RoleMiddleware
{
  private string $role;

  public function __construct(string $role)
  {
    $this->role = $role;
  }

  public function handle(Request $request, callable $next): Response
  {
    $auth = new AuthService();
    if (!$auth->check() || !$auth->hasRole($this->role)) {
      return new Response('Forbidden', 403);
    }
    return $next($request);
  }
}
