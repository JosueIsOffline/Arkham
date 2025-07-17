<?php

namespace JosueIsOffline\Framework\Routing;

class RouteLoader
{
  private array $routes = [];
  private string $routesPath;

  public function __construct(?string $routesPath = null)
  {
    $this->routesPath = $routesPath ?? BASE_PATH . '/routes';
  }

  /**
   * Carga todas las rutas automáticamente desde archivos PHP en el directorio routes
   */
  public function loadRoutes(): array
  {
    if (!is_dir($this->routesPath)) {
      throw new \RuntimeException("El directorio de rutas no existe: {$this->routesPath}");
    }

    // Buscar todos los archivos PHP en el directorio routes
    $routeFiles = glob($this->routesPath . '/*.php');

    foreach ($routeFiles as $routeFile) {
      $this->loadRouteFile($routeFile);
    }

    return $this->routes;
  }

  /**
   * Carga rutas desde un archivo específico
   */
  private function loadRouteFile(string $filePath): void
  {
    $routes = require $filePath;

    if (!is_array($routes)) {
      throw new \RuntimeException("El archivo de rutas debe retornar un array: {$filePath}");
    }

    $this->routes = array_merge($this->routes, $routes);
  }

  /**
   * Permite cargar rutas desde un directorio específico
   */
  public function loadFromDirectory(string $directory): array
  {
    $this->routesPath = $directory;
    return $this->loadRoutes();
  }

  /**
   * Permite registrar rutas manualmente
   */
  public function addRoute(string $method, string $path, array $handler): void
  {
    $this->routes[] = [$method, $path, $handler];
  }

  /**
   * Permite registrar múltiples rutas de una vez
   */
  public function addRoutes(array $routes): void
  {
    foreach ($routes as $route) {
      if (count($route) === 3) {
        $this->routes[] = $route;
      }
    }
  }

  /**
   * Obtiene todas las rutas cargadas
   */
  public function getRoutes(): array
  {
    return $this->routes;
  }

  /**
   * Limpia todas las rutas cargadas
   */
  public function clearRoutes(): void
  {
    $this->routes = [];
  }
}
