<?php

use App\Controllers\BookController;
use App\Controllers\HomeController;
use App\Controllers\DatabaseWizardController;

return [
  ['GET', '/', [HomeController::class, 'index']],
  ['GET', '/books/{id:\d+}', [BookController::class, 'show']],
  ['GET', '/books/create', [BookController::class, 'create']],
  ['GET', '/wizard', [DatabaseWizardController::class, 'index']],
  ['POST', '/wizard', [DatabaseWizardController::class, 'index']]
];
