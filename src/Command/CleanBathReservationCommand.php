<?php

namespace App\Command;

use App\Entity\BathReservation;
use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CleanBathReservationCommand extends Command
{
    private $container;

    public function __construct($name = null, ContainerInterface $container)
    {
      parent::__construct($name);
      $this->container = $container;
    }

    protected function configure()
    {
      $this
        ->setName('reservations:clean')
        ->setDescription('Deletes all bath reservations which is expired.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $output->writeln('*** ** ***');
      $output->writeln('Начинаем чистить бронирования. Подождите.');
      $unpayedReservations = $this->container->get('doctrine')->getRepository(BathReservation::class)->findNotPayedAndExpired();
  
      $output->writeln('');
      $output->writeln('Всего неоплаченных бронирований '.count($unpayedReservations ?? []).'.');
  
      if ($unpayedReservations) {
          $output->writeln('Доставляем...');
  
          $em = $this->container->get('doctrine')->getManager();
          $batchSize = 20;
          $i = 1;
          foreach ($unpayedReservations as $unpayedReservation) {
            $em->remove($unpayedReservation);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
          }
          $em->flush();
          $output->writeln('Удалено всего'.' '.count($unpayedReservations).' неоплаченных бронирований.');
      }
      $output->writeln('');
      $output->writeln('Все неоплаченные бронирования удалены (наверное).');
      $output->writeln('*** ** ***');
      return 1;
    }

}