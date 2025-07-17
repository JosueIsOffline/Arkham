<?php

namespace JosueIsOffline\Framework\Database\Setup;

use App\Controllers\DatabaseWizardController;

class WizardRoutes
{
  private const WIZARD_ROUTE = '/database-setup';
  public static function register(): array
  {
    return [
      ['GET', self::WIZARD_ROUTE, [DatabaseWizardController::class, 'index']],
      ['POST', self::WIZARD_ROUTE, [DatabaseWizardController::class, 'index']],
    ];
  }

  public static function getRoute(): string
  {
    return self::WIZARD_ROUTE;
  }

  public static function shouldRegister(): bool
  {
    // Only register wizard routes if no database config exists
    $bootstrap = new \JosueIsOffline\Framework\Database\DatabaseBootstrap();
    return !$bootstrap->configFileExist();
  }
}
