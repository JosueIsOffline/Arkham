<?php

namespace JosueIsOffline\Framework\Middleware;

use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\FrameworkBootstrap;

class SetupMiddleware
{
  public function handle(Request $request, callable $next): Response
  {
    // Check if we need to redirect to setup
    $redirectPath = FrameworkBootstrap::handleSetupRedirect($request);

    if ($redirectPath) {
      return new Response('', 302, ['Location' => $redirectPath]);
    }

    // Continue with normal request handling
    return $next($request);
  }
}
