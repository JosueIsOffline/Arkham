<?php

namespace JosueIsOffline\Framework\Http;

use FastRoute\RouteCollector;
use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Routing\RouteLoader;

use function FastRoute\simpleDispatcher;

class Kernel
{

  private RouteLoader $routeLoader;

  public function __construct(?RouteLoader $routeLoader = null)
  {
    $this->routeLoader = $routeLoader ?? new RouteLoader();
  }

  public function handle(Request $request): Response
  {
    $dispatcher = simpleDispatcher(function (RouteCollector $routerCollector) {
      $routes = $this->routeLoader->loadRoutes();

      foreach ($routes as $route) {
        $routerCollector->addRoute($route[0], $route[1], $route);
      }
    });

    $routeInfo = $dispatcher->dispatch(
      $request->getMethod(),
      $request->getPathInfo()
    );

    $status = $routeInfo[0];

    if ($status === \FastRoute\Dispatcher::FOUND) {
      [, $route, $vars] = $routeInfo;
      [$method, $path, $handler] = $route;
      $protection = $route[3] ?? null;

      if ($protection) {
        $response = $this->applyProtection($request, $protection);
        if ($response) return $response;
      }

      [$controller, $method] = $handler;
      $controllerInstance = new $controller;

      if ($controllerInstance instanceof AbstractController) {
        $controllerInstance->setRequest($request);
      }

      return call_user_func_array([$controllerInstance, $method], $vars);
    } elseif ($status === \FastRoute\Dispatcher::NOT_FOUND) {
      return new Response('404 Not Found', 404);
    } elseif ($status === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
      return new Response('405 Method Not Allowed', 405);
    }

    return new Response('500 Internal Server Error', 500);
  }

  private function applyProtection(Request $request, $protection): ?Response
  {
    if (is_string($protection)) {
      if ($protection === 'auth') {
        $middleware = new \JosueIsOffline\Framework\Middleware\AuthMiddleware();
        return $this->checkMiddleware($request, $middleware);
      } elseif (strpos($protection, 'role:') === 0) {
        $role = substr($protection, 5);
        $middleware = new \JosueIsOffline\Framework\Middleware\RoleMiddleware($role);
        return $this->checkMiddleware($request, $middleware);
      }
    } elseif (is_array($protection)) {
      foreach ($protection as $item) {
        if (is_string($item)) {
          if ($item === 'auth') {
            $middleware = new \JosueIsOffline\Framework\Middleware\AuthMiddleware();
          } elseif (strpos($item, 'role:') === 0) {
            $role = substr($item, 5);
            $middleware = new \JosueIsOffline\Framework\Middleware\RoleMiddleware($role);
          } else {
            continue;
          }
        } else {
          $middleware = new $item();
        }

        $result = $this->checkMiddleware($request, $middleware);
        if ($result) return $result;
      }
    }

    return null;
  }

  private function checkMiddleware(Request $request, $middleware): ?Response
  {
    $response = $middleware->handle($request, function ($req) {
      return new Response('', 200);
    });

    return $response->getStatus() !== 200 ? $response : null;
  }

  public function setRouterLoader(RouteLoader $routeLoader): void
  {
    $this->routeLoader = $routeLoader;
  }
}
