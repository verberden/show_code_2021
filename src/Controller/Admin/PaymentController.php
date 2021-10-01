<?php

namespace App\Controller\Admin;

use App\Entity\BathPayment;
use App\Entity\BathReservation;
use App\Entity\Category;
use App\Entity\House;
use App\Entity\Order;
use App\Entity\Payment;
use App\Form\Type\HouseType;
use App\Form\Type\PaymentType;
use App\Service\NotificationService;
use App\Service\Sberbank;
use App\Service\TelegramService;
use DateTimeImmutable;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
  /** @var Sberbank $sberbank */
  private $sberbank;

  /** @var NotificationService $notificationService */
  private $notificationService;

  /** @var TelegramService $telegramService */
  private $telegramService;

  public function __construct(Sberbank $sberbank, NotificationService $notificationService, TelegramService $telegramService)
  {
    $this->sberbank = $sberbank;
    $this->notificationService = $notificationService;
    $this->telegramService = $telegramService;
  }

  public function moneyBack(Request $request, int $id, $paymentType = 'order_payment'): Response
  {
    $amount = $request->request->get('amount');
    $routeName = $paymentType === 'order_payment' ? 'admin_order_show' : 'admin_bathreservation_show';

    if (!$amount) {
      $this->addFlash('error', 'Сумма возврата должна быть больше 0.');
      return $this->redirectToRoute($routeName, ['id'=> $id]);
    }

    $amount = $amount * 100;

    if ($paymentType === 'order_payment') {
      $order = $this->getDoctrine()->getRepository(Order::class)->find($id);
      $stop_date = $order->getDeliveryTime()->format('Y-m-d');
    } else {
      $order = $this->getDoctrine()->getRepository(BathReservation::class)->find($id);
      $stop_date = $order->getDate()->format('Y-m-d');
    }

    if (!$order) {
      if ($paymentType === 'order_payment') {
        $this->addFlash('error', 'Заказ не найден.');
        return $this->redirectToRoute('admin_orders');
      } else {
        $this->addFlash('error', 'Бронирование не найдено.');
        return $this->redirectToRoute('admin_bathreservations');
      }
    }
    
    $stop_date = new DateTimeImmutable($stop_date);
    $stop_date->modify('+1 day');
    $interval = intval(date_diff(new DateTimeImmutable(), $stop_date)->format('%R%a'));
    if ($paymentType === 'order_payment' && ($order->isDelivered() || $interval < 0 )) {
      if($request->isXmlHttpRequest()) {
        return new Response('Возврат денег невозможен.', 404);
      }
      $this->addFlash('info', 'Возврат денег невозможен.');
      return $this->redirectToRoute($routeName, ['id'=> $id]);
    } elseif ($paymentType === 'bath_payment' && $interval < 0 ) {
      if($request->isXmlHttpRequest()) {
        return new Response('Возврат денег невозможен.', 404);
      }
      $this->addFlash('info', 'Возврат денег невозможен.');
      return $this->redirectToRoute($routeName, ['id'=> $id]);      
    }

    if ($paymentType === 'order_payment') {
      $payment = $this->getDoctrine()->getRepository(Payment::class)->findOneBy(['orderId' => $id, 'status' => Payment::PAYED]);
    } else {
      $payment = $this->getDoctrine()->getRepository(BathPayment::class)->findOneBy(['bathReservation' => $order, 'status' => Payment::PAYED]);
    }

    if (!$payment) {
      $this->addFlash('error', 'Заказ не оплачен.');
      return $this->redirectToRoute($routeName, ['id'=> $id]);
    }

    try {
      $refundResult = $this->sberbank->refundOrder($payment->getSystemId(), $amount );
    } catch(\Exception $e) {
      if($request->isXmlHttpRequest()) {
        return new Response($e->getMessage(), 404);
      }
      $this->addFlash('error', $e->getMessage());
      return $this->redirectToRoute($routeName, ['id'=> $id]);     
    }

    $em = $this->getDoctrine()->getManager();

    $status = $amount === $payment->getAmount() ? Payment::RETURN_FULL : Payment::RETURN_PARTIAL;
    if ($paymentType === 'order_payment') {
      $newPayment = new Payment();
      $newPayment->setOrder($order);
      $payments = $order->getPayments();
    } else {
      $newPayment = new BathPayment();
      $newPayment->setBathReservation($order);
      $payments = $order->getBathPayments();
    }

    $newPayment->setSystemId($payment->getSystemId());
    $newPayment->setAmount(-1 * $amount);
    $newPayment->setUrl('');
    $newPayment->setStatus($status);
    $newPayment->setPaymentSystem($payment->getPaymentSystem());
    $newPayment->setBankInfo($refundResult);
    $newPayment->setProcessingSystem(Sberbank::APP_PROCESSING);

    $order->setPaymentSum($order->getPaymentSum() - $amount);

    $em->persist($newPayment);

    $em->flush();

    $paymentSum = 0;
    foreach ($payments as $payment) {
      $paymentSum += $payment->getAmount();
    }

    if ($paymentSum === 0) {
      $order->setStatus(Order::REFUNDED_FULL_STATUS);

      $em->flush();
    }

    if ($paymentType === 'order_payment') {
      $message = 'Совершен возврат денежных средств по заказу №'.$id.' в размере '.($amount/100).'₽';
    } else {
      $message = 'Совершен возврат денежных средств по бронированию №'.$id.' в размере '.($amount/100).'₽';
    }
    // $this->telegramService->sendMessage($order->getCustomer()->getTelegramChatId(), $message);
    try {
      if ($paymentType === 'order_payment') {
        $this->notificationService->send($order->getNotifyToken(), [    
          'title' => 'Возврат денежных средств',
          'body' => $message
        ]);
      }
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
      // return $this->redirectToRoute('admin_order_show', ['id'=> $id]);
      if($request->isXmlHttpRequest()) {
        return new Response($e->getMessage(), 200);
      }
    }

    if($request->isXmlHttpRequest()) {
      return new Response('Success');
    }

    $this->addFlash('info', $message);
    return $this->redirectToRoute($routeName, ['id'=> $id]);
  }
}
