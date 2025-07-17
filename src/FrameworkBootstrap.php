<?php

namespace JosueIsOffline\Framework;

use JosueIsOffline\Framework\Database\DatabaseBootstrap;
use JosueIsOffline\Framework\Database\Setup\WizardRoutes;

class FrameworkBootstrap
{
  public static function boot(): void
  {
    $dbBootstrap = new DatabaseBootstrap();
    $dbBootstrap->boot();
  }

  public static function registerSystemRoutes($routeLoader): void
  {
    if (self::needsSetup()) {
      $wizardRoutes = WizardRoutes::register();
      foreach ($wizardRoutes as $route) {
        $routeLoader->addRoute($route[0], $route[1], $route[2]);
      }
    }
  }

  public static function needsSetup(): bool
  {
    $dbBootstrap = new DatabaseBootstrap();
    return !$dbBootstrap->configFileExist();
  }

  public static function handleSetupRedirect($request): ?string
  {
    if (self::needsSetup()) {
      $path = $request->getPathInfo();
      $wizardRoute = WizardRoutes::getRoute();
      if ($path !== $wizardRoute) {
        return $wizardRoute;
      }
    }
    return null;
  }
}
