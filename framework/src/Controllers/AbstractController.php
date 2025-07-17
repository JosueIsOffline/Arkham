<?php

namespace JosueIsOffline\Framework\Controllers;

use JosueIsOffline\Framework\Http\Request;
use JosueIsOffline\Framework\Http\Response;
use JosueIsOffline\Framework\View\ViewResolver;

abstract class AbstractController
{
  protected ?Request $request = null;
  protected ViewResolver $viewResolver;

  public function __construct()
  {
    $this->viewResolver = new ViewResolver();
  }

  public function render(string $template, ?array $data = []): Response
  {

    $content = $this->viewResolver->render($template, $data);

    $response = new Response($content);

    return $response;
  }

  public function setRequest(Request $request): void
  {
    $this->request = $request;
  }

  public function setViewResolver(ViewResolver $viewResolver): void
  {
    $this->viewResolver = $viewResolver;
  }

  protected function viewExists(string $template): bool
  {
    return $this->viewResolver->viewExists($template);
  }

  protected function renderOrDefault(string $template, string $defaultTemplate, array $data = []): Response
  {
    $templateToRender = $this->viewExists($template) ? $template : $defaultTemplate;
    return $this->render($templateToRender, $data);
  }
}
