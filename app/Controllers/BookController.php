<?php

namespace App\Controllers;

use JosueIsOffline\Framework\Controllers\AbstractController;
use JosueIsOffline\Framework\Http\Response;

class BookController extends AbstractController
{
  public function show(int $id): Response
  {
    return $this->render("book.html.twig", [
      'id' => $id,
    ]);
  }

  public function create(): Response
  {
    return $this->render('create-book.html.twig');
  }
}
