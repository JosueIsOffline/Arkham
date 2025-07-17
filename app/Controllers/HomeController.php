<?php

namespace App\Controllers;

use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Http\Response;

class HomeController extends AbstractController
{
  public function index(): Response
  {


    return $this->render("home.html.twig");
  }
}
