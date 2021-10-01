<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Form\Type\CategoryType;
use App\Form\Type\OrderType;
use App\Service\NotificationService;
use App\Service\OrderService;
use App\Service\Sberbank;
use App\Service\TelegramService;
use App\Service\UserService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
  private $serializer;

  private $userService;

  /** @var Sberbank $sberbank */
  private $sberbank;

  /** @var TelegramService $telegramService */
  private $telegramService;

  /** @var NotificationService $notificationService */
  private $notificationService;

  public function __construct(
    SerializerInterface $serializer, 
    UserService $userService, 
    Sberbank $sberbank,
    NotificationService $notificationService,
    TelegramService $telegramService)
  {
    $this->userService = $userService;
    $this->serializer = $serializer;
    $this->sberbank = $sberbank;
    $this->notificationService = $notificationService;
    $this->telegramService = $telegramService;
  }

  public function index(): Response
  {
    $orders = $this->getDoctrine()->getRepository(Order::class)->findBy([], ['id' => 'DESC']);
    $deliveryMen = $this->userService->findAllDeliveryMen();

    $statuses = Order::STATUSES;
    return $this->render('admin/order/index.html.twig', [
        'orders' => $orders,
        'statuses' => $statuses,
        'buttonStatuses' => Order::STATUSES_FOR_BBUTTON,
        'btnColors' => Order::STATUS_BTN_COLOR,
        'rowColor' => Order::STATUS_COLOR,
        'deliveryMen' => $deliveryMen,
    ]);
  }

  public function edit(Request $request, ?int $id): Response
  {
    if ($id) {
      /** @var Order|null $order */
      $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['id' => $id]);
      if (!$order) return $this->redirectToRoute('admin_orders');
    } else {
      return $this->redirectToRoute('admin_orders');
    }

    $originalItems = new ArrayCollection();

    foreach ($order->getOrderItems() as $item) {
      $originalItems->add($item);
    }

    $form = $this->createForm(OrderType::class, $order);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
      $itemsCost = 0;
      /** @var OrderItem $item */
      foreach ($order->getOrderItems() as $item) {
        $itemsCost += $item->getCost();
      }

      if ($itemsCost > $order->getPaymentSum()) {
        $this->addFlash('error', 'Стоимость позиций больше чем оплачено.');
        return $this->redirect($this->generateUrl('admin_order_show', ['id' => $id]));
      }

      $em = $this->getDoctrine()->getManager();
      foreach ($originalItems as $item) {
        if (false === $order->getOrderItems()->contains($item)) {
            $em->remove($item);
        }
      }

      $em->flush();

      // var_dump('hehe'); die();
      return $this->redirect($this->generateUrl('admin_order_show', ['id' => $id]));
    }

    return $this->render('admin/order/edit.html.twig', [
      'form' => $form->createView(),
      'factItemsCost' => $order->getFactOrderItemsCost(),
    ]);
  }

  public function show(Request $request, $id): Response
  {

    $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['id' => $id]);

    $stop_date = $order->getDeliveryTime()->format('Y-m-d');
    $stop_date = new DateTimeImmutable($stop_date);
    $stop_date->modify('+1 day');
    $interval = intval(date_diff(new DateTimeImmutable(), $stop_date)->format('%R%a'));
    $canRefund = true;

    if ($order->isDelivered() || $interval < 0) $canRefund = false;
    return $this->render('admin/order/show.html.twig', [
        'order' => $order,
        'orderStatuses' => Order::STATUSES,
        'paymentStatuses' => Payment::STATUSES,
        'refundedStatus' => Payment::RETURN_FULL,
        'canRefund' => $canRefund,
    ]);
  }

  public function pay(Request $request, $id): Response
  {

    /** @var Order $order */
    $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['id' => $id]);
    if (!$order) {
      $this->addFlash('error', 'Произошла ошибка, попробуйте позже');
      return $this->redirectToRoute('admin_orders');
    }
    $url = $this->sberbank->init($order, ($order->getCost()),'Заказ №'.$order->getId());
    if ($url) {
      return $this->redirect($url);
    }

    return $this->redirectToRoute('admin_orders');
  }

  public function updateStatus(Request $request, $id): Response
  {
    $status = $request->request->get('status');
    if (!Order::STATUSES[$status])
      throw new NotFoundException('Неверный статус.');

    $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['id' => $id]);

    if (!$order)
      throw new NotFoundException('Заказ не найден.');

    $order->setStatus($status);
    $em = $this->getDoctrine()->getManager();

    $em->flush();

    $lowCaseStatusText = mb_strtolower($order->getStatusText());

    $deliveryMan = $order->getDeliveryMan();

    if ($deliveryMan && $status !== Order::DELIVERED_STATUS && $deliveryMan->getEnableOrderNotification()) {
      $this->telegramService->sendMessage($deliveryMan->getTelegramChatId(), 'Статус заказа №'.$order->getId().' изменился на '.$lowCaseStatusText.'.');
    }    
    
    try {
      $this->notificationService->send($order->getNotifyToken(), [    
        'title' => 'Изменение статуса заказа',
        'body' => 'Заказ №'.$order->getId().' '.$lowCaseStatusText.'.',
        'userAction' => 'go on delivery status'
      ]);
    } catch (\Exception $e) {
      // TODO:logging
    }

    return new Response('Success');
  }

  public function setDeliveryMan(Request $request, $id): Response
  {
    $deliveryManId = $request->request->get('id');
    if (!$deliveryManId)
      throw new NotFoundException('Не указан id доставщика.');

    $deliveryMan = $this->userService->findDeliveryMan($deliveryManId);
    if (!$deliveryMan)
      throw new NotFoundException('Нет доставщика с таким id.');

    $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['id' => $id]);

    if (!$order)
      throw new NotFoundException('Заказ не найден.');

    $order->setDeliveryMan($deliveryMan);
    $lowCaseStatusText = mb_strtolower($order->getStatusText());
    $em = $this->getDoctrine()->getManager();
    if ($deliveryMan->getEnableOrderNotification()) {
      $this->telegramService->sendMessage($deliveryMan->getTelegramChatId(), 'Вам назначен заказ №'.$order->getId().' со статусом '.$lowCaseStatusText.'.');
    }

    $em->flush();
    return new Response('Success');
  }
}