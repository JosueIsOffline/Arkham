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
        $routerCollector->addRoute(...$route);
      }
    });

    $routeInfo = $dispatcher->dispatch(
      $request->getMethod(),
      $request->getUri()
    );

    $status = $routeInfo[0];

    if ($status === \FastRoute\Dispatcher::FOUND) {
      [, [$controller, $method], $vars] = $routeInfo;

      $controller = new $controller;

      if ($controller instanceof AbstractController) {
        $controller->setRequest($request);
      }

      return call_user_func_array([$controller, $method], $vars);
    } elseif ($status === \FastRoute\Dispatcher::NOT_FOUND) {

      return new Response('404 Not Found', 404);
    } elseif ($status === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
      // Optional: get allowed methods for this route
      $allowedMethods = $routeInfo[1];
      return new Response('405 Method Not Allowed', 405);
    }

    return new Response('500 Internal Server Error', 500);
  }

  public function setRouterLoader(RouteLoader $routeLoader): void
  {
    $this->routeLoader = $routeLoader;
  }
}
