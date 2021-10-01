<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommand extends Command
{
    private $container;

    public function __construct($name = null, ContainerInterface $container, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($name);
        $this->container = $container;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Just create user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        $loginQuestion = new Question('Enter login: ');
        $passwordQuestion = new Question('Enter password: ');
        $passwordVerifyQuestion = new Question('Retype password: ');
        $roleQuestion = new ChoiceQuestion(
            'Select role (default is ROLE_USER)',
            [User::ROLE_ADMIN_CAFE, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN],
            0
        );
        $roleQuestion->setErrorMessage('Role %s is invalid.');

        $passwordQuestion->setHidden(true);
        $passwordVerifyQuestion->setHidden(true);

        $login = $questionHelper->ask($input, $output, $loginQuestion);
        $password = $questionHelper->ask($input, $output, $passwordQuestion);
        $passwordVerify = $questionHelper->ask($input, $output, $passwordVerifyQuestion);
        $role = $questionHelper->ask($input, $output, $roleQuestion);

        if ($login && $password && $passwordVerify && $password == $passwordVerify) {
            $em = $this->container->get('doctrine')->getManager();

            $user = new User();
            $user->setLogin($login);
            $user->setEnabled(true);
            $user->addRole($role);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $em->persist($user);
            $em->flush();

            $output->writeln('User created');
        }

        return 1;
    }

}
