<?php

use JosueIsOffline\Framework\Http\Kernel;
use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\Routing\RouteLoader;

define("BASE_PATH", dirname(__DIR__));

require_once BASE_PATH . "/vendor/autoload.php";

$request = Request::create();

$routeLoader = new RouteLoader();

$kernel = new Kernel($routeLoader);
$response = $kernel->handle($request);
$response->send();
