<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    /** @var UserPasswordHasherInterface $passwordHasher */
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @Route("/admin/user", name="admin_user")
     */
    public function index(): Response
    {
        
        $users = $this->getDoctrine()->getRepository(User::class)->findAllNotSuperAdmin();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'roles' => User::ROLES,
            'botName' => $this->container->get('parameter_bag')->get('telegram_bot_name'),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = new User();
        $user->setEnabled(true);
        $form = $this->createForm(UserType::class, $user, [
            'require_plain_password' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            if (!$user->getId()) {
                $em->persist($user);
            }

            $em->flush();

            return $this->redirect($this->generateUrl('admin_users'));
        }
        return $this->render('admin\user\edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    public function update(Request $request, $id): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword ) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            $em->flush();

            return $this->redirect($this->generateUrl('admin_users'));
        }

        return $this->render('admin\user\edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    public function updateHash($id): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $user->generateTelegramHash();
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return new Response($user->getTelegramHash());
    }
}
