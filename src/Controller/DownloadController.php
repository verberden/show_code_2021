<?php

namespace App\Controller;

use App\Form\Type\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class DownloadController extends AbstractController
{
  public function index(Request $request)
  {

    return $this->render('download\index.html.twig', [
    ]);
  }
}