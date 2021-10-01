<?php

namespace App\Controller\Api\Payment;

use App\Entity\BathPayment;
use App\Entity\BathReservation;
use App\Entity\House;
use App\Entity\Order;
use App\Entity\Payment;
use App\Repository\HouseRepository;
use App\Repository\OrderRepository;
use App\Service\NotificationService;
use App\Service\OrderService;
use App\Service\PaymentService;
use App\Service\Sberbank;
use App\Service\TelegramService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Voronkovich\SberbankAcquiring\OrderStatus;

/**
 * House controller.
 */
final class SberbankController extends AbstractFOSRestController
{
  private $serializer;

  /** @var OrderService $orderService */
  private $orderService;

  /** @var NotificationService $notificationService */
  private $notificationService;

  /** @var TelegramService $telegramService */
  private $telegramService;

  /** @var Sberbank $sberbank */
  private $sberbank;

  /** @var LoggerInterface $logger */
  private $logger;

  /** @var PaymentService $paymentService */
  private $paymentService;

  public function __construct(
      SerializerInterface $serializer,
      OrderService $orderService,
      NotificationService $notificationService,
      TelegramService $telegramService,
      Sberbank $sberbank,
      LoggerInterface $paymentLogger,
      PaymentService $paymentService)
  {
    $this->serializer = $serializer;
    $this->orderService = $orderService;
    $this->notificationService = $notificationService;
    $this->telegramService = $telegramService;
    $this->sberbank = $sberbank;
    $this->logger = $paymentLogger;
    $this->paymentService = $paymentService;
  }

  private function getEntityManager(): EntityManager
  {
    return $this->getDoctrine()->getManager();
  }

  private function getOrderRepo(): OrderRepository
  {
    return $this->getDoctrine()->getRepository(Order::class);
  }

  /**
   * Gets a Houses resources
   * @return View
   */
  public function success(Request $request): View
  {
    $orderId = $request->query->get('orderId');
    $paymentType = $request->query->get('payment_type');
    if (!$orderId || !$paymentType) {
      return View::create('Не указаны обязательные параметры.', Response::HTTP_BAD_REQUEST);
    }

    $PaymentService = $this->paymentService->init($paymentType);

    $order = $PaymentService->getOrder($orderId, $paymentType);

    if (!$order) {
      return View::create('Не найден заказ.', Response::HTTP_NOT_FOUND);
    }

    $result = $this->sberbank->getOrderStatus($orderId);
    // $this->logger->info(
    //     'SberbankController for orderId=' . $orderId . '. Нет обязательных параметров. Data: ' . $request->getContent()
    // );

    if (!OrderStatus::isDeposited($result['orderStatus'])) {
      return View::create('Заказ не оплачен.', Response::HTTP_NOT_FOUND);
    }

    $flagNewPay = false;

    if ($order->getStatus() != Order::PAYED_STATUS) {
      $order->setStatus(Order::PAYED_STATUS);
      $order->setPaymentSum($result['amount']);
    }

    $em = $this->getEntityManager();
    $em->getConnection()->beginTransaction();
    try {
      $em->flush();
      $payment = $PaymentService->getPayment($orderId, $paymentType);
      if ($payment->getStatus() != Payment::PAYED) {
        $payment->setStatus(Payment::PAYED);
        $payment->setBankInfo($result);
        $payment->setAmount($result['amount']);
        $flagNewPay = true;
      }

      $em->flush();
      $em->getConnection()->commit();
    } catch (Exception $e) {
      $em->getConnection()->rollBack();
      throw $e;
    }

    $message = $PaymentService->generateSuccesPayMessage($order->getId());
    // $this->telegramService->sendMessage($order->getCustomer()->getTelegramChatId(), $message);
    if ($flagNewPay) {
      $this->telegramService->sendToAllAdmins('Оплачен заказ: ' . $this->generateUrl('admin_order_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $paymentType);

      if (isset($order->getNotifyToken) && $order->getNotifyToken()) {
        try {
          $this->notificationService->send($order->getNotifyToken(), [
              'title' => 'Изменение статуса заказа',
              'body' => $message
          ]);
        } catch (\Exception $e) {
          return View::create($e->getMessage(), Response::HTTP_OK);
        }
      }
    }

    return View::create($message, Response::HTTP_OK);
  }

  public function fail(Request $request): View
  {
    $orderId = $request->query->get('orderId');
    $paymentType = $request->query->get('payment_type');
    if (!$orderId || !$paymentType) {
      return View::create('Не указаны обязательные параметры.', Response::HTTP_BAD_REQUEST);
    }

    $PaymentService = $this->paymentService->init($paymentType);

    $order = $PaymentService->getOrder($orderId, $paymentType);

    if (!$order) {
      return View::create('Не найден заказ.', Response::HTTP_NOT_FOUND);
    }

    $result = $this->sberbank->getOrderStatus($orderId);

    if (OrderStatus::isDeclined($result['orderStatus'])) {
      $order->setStatus(Order::DECLINED_STATUS);
    } else {
      $order->setStatus(Order::PAYMENT_ERROR_STATUS);
    }

    $em = $this->getEntityManager();
    if ($paymentType === 'bath_payment') {
      if ($result['actionCode'] == '-2007') {
        $slots = $order->getSlots();
        foreach ($slots as $slot) {
          $em->remove($slot);
        }
        $em->remove($order);
      }
    }

    $em->getConnection()->beginTransaction();

    try {
      $em->flush();
      $payment = $PaymentService->getPayment($orderId, $paymentType);
      $payment->setStatus(Payment::PAYMENT_ERROR);
      $payment->setBankInfo($result);

      $em->flush();
      $em->getConnection()->commit();
    } catch (Exception $e) {
      $em->getConnection()->rollBack();
      throw $e;
    }

    return View::create(null, Response::HTTP_NOT_FOUND);
  }

  public function paymentDo(Request $request, string $processingSystem): View
  {
    if (!in_array($processingSystem, [Sberbank::APPLE_PROCESSING, Sberbank::GOOGLE_PROCESSING])) {
      return View::create('Неверная система оплаты.', Response::HTTP_NOT_FOUND);
    }
    $data = json_decode($request->getContent(), true);
    if (!isset($data['paymentToken']) || !isset($data['hash']) || !isset($data['payment_type'])) {
      $this->logger->error(
          'SberbankController Error in ' . Sberbank::PROCESSING_SYSTEM_TEXT[$processingSystem] . '. Нет обязательных параметров. Data: ' . $request->getContent()
      );
      return View::create('Нет обязательных параметров.', Response::HTTP_BAD_REQUEST);
    }

    $paymentToken = $data['paymentToken'];
    $orderHash = $data['hash'];
    $paymentType = $data['payment_type'];

    $PaymentService = $this->paymentService->init($paymentType);

    $order = $PaymentService->getOrderByHash($orderHash);

    if (!$order) {
      return View::create('Не найден заказ с таким hash.', Response::HTTP_NOT_FOUND);
    }

    $merchant = $this->getParameter('sberbank_merchant');

    try {
      $result = $this->sberbank->payWith($processingSystem, [
        'orderId' => 'app_' . bin2hex(random_bytes(10)),
        'merchant' => $merchant,
        'paymentToken' => $paymentToken,
        'amount' => $order->getCost(),
        'description' => 'Заказ №' . $order->getId(),
      ]);
    } catch (\Exception $e) {
      $this->logger->error('SberbankController  Error in ' . Sberbank::PROCESSING_SYSTEM_TEXT[$processingSystem] . ', payment type is' . $paymentType . ', message=' . $e->getMessage() . ' code=' . $e->getCode());

      return View::create(
        'Exception Error in ' . Sberbank::PROCESSING_SYSTEM_TEXT[$processingSystem] . ', payment type is' . $paymentType . ', message=' . $e->getMessage() . ' code=' . $e->getCode(),
        Response::HTTP_BAD_REQUEST
      );
    }

    if (
      (isset($result['success']) && $result['success'] === true) ||
      (isset($result['orderStatus']) && $result['orderStatus']['errorCode'] === "0") ||
      (isset($result['data']) && (!isset($result['error']) || $result['error']['code'] === 0))
    ) {

      $sberbankStatus = $this->sberbank->getOrderStatus($result['data']['orderId']);
      $em = $this->getEntityManager();

      $waitingPayments = [];
      if ($paymentType === 'bath_payment') {
        $waitingPayments = $this->getDoctrine()->getRepository(BathPayment::class)->findWaitingPayments($orderHash);
      }

      if (!OrderStatus::isDeposited($sberbankStatus['orderStatus'])) {
        if (!OrderStatus::isDeclined($sberbankStatus['orderStatus']) && $processingSystem === Sberbank::GOOGLE_PROCESSING) {

          $url = $this->sberbank->getAcsUrlForGoogle($result['data']['orderId']);
          $paymentNew = $PaymentService->generatePayment($result['data']['orderId'], Payment::WAIT, $sberbankStatus['amount'], $order, $processingSystem, $url, Sberbank::PAYMENT_SYSTEM_CODE);
          $em->persist($paymentNew);
          if (count($waitingPayments)) {
            foreach($waitingPayments as $waitingPayment) {
              $em->remove($waitingPayment);
            }
          }
          $em->flush();

          return View::create(['url' => $url], Response::HTTP_OK);
        }

        return View::create('Заказ не оплачен.', Response::HTTP_NOT_FOUND);
      }

      if (count($waitingPayments)) {
        foreach($waitingPayments as $waitingPayment) {
          $em->remove($waitingPayment);
        }
      }
      $paymentNew = $PaymentService->generatePayment($result['data']['orderId'], Payment::PAYED, $order->getCost(), $order, $processingSystem, null, Sberbank::PAYMENT_SYSTEM_CODE, $result);
      $em->persist($paymentNew);

      if ($order->getNotifyToken()) {
        try {
          $this->notificationService->send($order->getNotifyToken(), [
              'title' => 'Изменение статуса заказа',
              'body' => 'Заказ №' . $order->getId() . ' оплачен.'
          ]);
        } catch (\Exception $e) {
          return View::create($e->getMessage(), Response::HTTP_OK);
        }
      }

      $order->setStatus(Order::PAYED_STATUS);
      $order->setPaymentSum($order->getCost());
      $em->flush();

      return View::create(null, Response::HTTP_OK);
    }

    // $payment->setStatus(Payment::PAYMENT_ERROR);
    // $em->flush();
    return View::create(null, Response::HTTP_NOT_FOUND);
  }

  public function getPaymentUrl(Request $request, string $hash): View
  {
    $paymentType = $request->get('paymentType') ?? 'order_payment';
    $serializeGroupText = $paymentType === 'order_payment' ? 'order:item' : 'reservation:item';
    if (!$hash) {
      return View::create('Не указан hash.', Response::HTTP_BAD_REQUEST);
    }
    $PaymentService = $this->paymentService->init($paymentType);

    $order = $PaymentService->getOrderByHash($hash);

    if (!$order) {
      return View::create('Не найден заказ.', Response::HTTP_NOT_FOUND);
    }

    if ($paymentType === 'bath_payment') {
      /** @var BathSlot $firstSlot */
      [$firstSlot] = $order->getSlots();
      $schedule = $firstSlot->getBathschedule();
      [$startSlotTime] = explode('-', $firstSlot->getTime());
      $slotDate = new DateTimeImmutable($schedule->getDate()->format('Y-m-d').' '.$startSlotTime);
      if (($order->getExpiredAt())->modify('+15 minutes') <= $slotDate) {
        $order->setExpiredAt($order->getExpiredAt()->modify(sprintf("+%d minutes", BathReservation::PAYMENT_LIFETIME_MINUTES)));
      }
      // return View::create('Невозможно повторно оплатить бронь, время вышло.', Response::HTTP_NOT_FOUND);

      $waitingPayments = [];
      $waitingPayments = $this->getDoctrine()->getRepository(BathPayment::class)->findWaitingPayments($hash);
      if (count($waitingPayments)) {
        $em = $this->getEntityManager();
        foreach($waitingPayments as $waitingPayment) {
          $em->remove($waitingPayment);
        }
        $em->flush();
      }
    }
    
    $url = $this->sberbank->init($order, $order->getCost(), 'Заказ №' . $order->getId(), Sberbank::APP_PROCESSING, false, $paymentType);
    $serialized = json_decode($this->serializer->serialize(['url' => $url], 'json', ['groups' => [$serializeGroupText]]));

    return View::create($serialized, Response::HTTP_OK);
  }

  public function getPaymentStatus(string $systemId)
  {
    if (!$systemId) {
      return View::create('Не указан id.', Response::HTTP_BAD_REQUEST);
    }

    $result = $this->sberbank->getOrderStatus($systemId);
    return $result;
  }

  public function getAcsSberbankUrl(string $systemId)
  {
    return View::create(['url' => $this->sberbank->getAcsUrlForGoogle($systemId)], Response::HTTP_OK);
  }
}