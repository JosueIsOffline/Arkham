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
   * Load all routes automatically from PHP files
   * Recursively reads all .php files regardless of structure
   */
  public function loadRoutes(): array
  {
    if (!is_dir($this->routesPath)) {
      throw new \RuntimeException("Routes directory does not exist: {$this->routesPath}");
    }

    // Find all PHP files recursively using RecursiveIterator
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->routesPath)
    );

    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        $routes = require $file->getPathname();

        // Procesar rutas con middleware
        foreach ($routes as $route) {
          if (isset($route['middleware'])) {
            $this->routes[] = [
              $route['method'] ?? $route[0],
              $route['path'] ?? $route[1],
              $route['handler'] ?? $route[2],
              $route['middleware']
            ];
          } else {
            $this->routes[] = $route;
          }
        }
      }
    }

    return $this->routes;
  }

  /**
   * Load routes from a specific file
   */
  private function loadRouteFile(string $filePath): void
  {
    try {
      $routes = require $filePath;

      if (!is_array($routes)) {
        throw new \RuntimeException("Route file must return an array: {$filePath}");
      }

      foreach ($routes as $route) {
        // Formato original: ['GET', '/path', [Controller::class, 'method']]
        // Formato con auth: ['GET', '/path', [Controller::class, 'method'], 'auth']
        // Formato con role: ['GET', '/path', [Controller::class, 'method'], 'role:admin']
        // Formato con middleware: ['GET', '/path', [Controller::class, 'method'], [AuthMiddleware::class]]

        if (count($route) >= 4) {
          // Tiene middleware/auth
          $this->routes[] = $route;
        } else {
          // Ruta normal sin protección
          $this->routes[] = $route;
        }
      }
    } catch (\Exception $e) {
      throw new \RuntimeException("Error loading route file {$filePath}: " . $e->getMessage());
    }
  }

  /**
   * Load routes from a specific directory
   */
  public function loadFromDirectory(string $directory): array
  {
    $this->routesPath = $directory;
    $this->clearRoutes();
    return $this->loadRoutes();
  }

  /**
   * Register routes manually
   */
  public function addRoute(string $method, string $path, array $handler): void
  {
    $this->routes[] = [$method, $path, $handler];
  }

  /**
   * Register multiple routes at once
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
   * Get all loaded routes
   */
  public function getRoutes(): array
  {
    return $this->routes;
  }

  /**
   * Clear all loaded routes
   */
  public function clearRoutes(): void
  {
    $this->routes = [];
  }

  /**
   * Get debug information about found route files
   */
  public function getDebugInfo(): array
  {
    $info = [
      'routes_path' => $this->routesPath,
      'total_routes' => count($this->routes),
      'route_files' => []
    ];

    if (is_dir($this->routesPath)) {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->routesPath)
      );

      foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
          $info['route_files'][] = str_replace($this->routesPath . '/', '', $file->getPathname());
        }
      }
    }

    return $info;
  }
}
