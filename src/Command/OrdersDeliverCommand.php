<?php

namespace App\Command;

use App\Entity\Order;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrdersDeliverCommand extends Command
{
  /** @var ContainerInterface $container  */
  private $container;

  public function __construct($name = null, ContainerInterface $container)
  {
      parent::__construct($name);
      $this->container = $container;
  }

  protected function configure()
  {
      $this
          ->setName('orders:deliver')
          ->setDescription('Set delivering orders as delivered.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('*** ** ***');
    $yesterdayDate = date("Y-m-j", strtotime( '-1 days' ) );
    $output->writeln('Начинаем доставлять заказы за '.$yesterdayDate.'. Подождите.');
    $undeliveredOrders = $this->container->get('doctrine')->getRepository(Order::class)->findBy(['status' => Order::DELIVERING_STATUS]);

    $output->writeln('');
    $output->writeln('Всего недоставленых заказов '.count($undeliveredOrders ?? []).'.');

    if ($undeliveredOrders) {
        $output->writeln('Доставляем...');

        $em = $this->container->get('doctrine')->getManager();
        $batchSize = 20;
        $i = 1;
        $ordersIds = [];
        foreach ($undeliveredOrders as $order) {
            $order->setStatus(Order::DELIVERED_STATUS);
            ++$i;
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            array_push($ordersIds, $order->getId());
        }
        $em->flush();
        $output->writeln('Доставлены заказы №: '. implode(',', $ordersIds).'.');
    }
    $output->writeln('');
    $output->writeln(':) все заказы доставлены!');
    $output->writeln('*** ** ***');
    return 1;
  }

}
