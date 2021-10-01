<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EmptyController extends AbstractController
{
  public function __invoke(): Response
  {
      return new Response();
  }
}