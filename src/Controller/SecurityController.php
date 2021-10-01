<?php

namespace App\Controller;

use App\Form\Type\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(UserPasswordHasherInterface $passwordEncoder)
     {
         $this->passwordEncoder = $passwordEncoder;
     }

    public function login(Request $request, AuthenticationUtils $authUtils)
    {

        $lastLogin = $authUtils->getLastUsername();

        $form = $this->createForm(LoginType::class);
        $form->setData(['login' => $lastLogin]);
        $form->handleRequest($request);

        $register = $request->get('register');
        if (!$register) {
            $register = false;
        }

        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        return $this->render('Security/login.html.twig', [
            'lastLogin' => $lastLogin,
            'error' => $error,
            'flagRegister' => $register,
            'form' => $form->createView()
        ]);
    }
}
