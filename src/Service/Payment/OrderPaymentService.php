<?php

namespace App\Service\Payment;

use App\Entity\Error;
use App\Entity\Payment;
use App\Repository\BathPaymentRepository;
use App\Repository\BathReservationRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use App\Service\Sberbank;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;

class OrderPaymentService extends AbstractPaymentServiceClass {

  /** @var OrderService $orderService */
  private $orderService;

  /** @var PaymentRepository $paymentRepository */
  private $paymentRepository;

  public function __construct(
    OrderService $orderService,
    PaymentRepository $paymentRepository)
  {
    $this->orderService = $orderService;
    $this->paymentRepository = $paymentRepository;
  }

  public function getOrder(string $systemId, $paymentType = 'order_payment')
  {
    return $this->orderService->findByPaymentOrderId($systemId);
  }

  public function getOrderByHash(string $hash)
  {
    return $this->orderService->findOneByHash($hash);
  }

  public function getPayment(string $systemId, $paymentType = 'order_payment') {

    return $this->paymentRepository->findOneBy(['systemId' => $systemId]);
  }
  public function generateSuccesPayMessage(string $orderId) {
    return 'Заказ №' . $orderId . ' оплачен.';
  }

  public function generatePayment(string $orderId, string $status, int $amount, $order, string $processingSystem,  ?string $url, $paymentSystemCode = Sberbank::PAYMENT_SYSTEM_CODE, $bankInfo = null)
  {
    /** @var Payment $payment */
    $payment = new Payment();
    $payment->setSystemId($orderId);
    $payment->setPaymentSystem($paymentSystemCode);
    $payment->setUrl($url);
    $payment->setStatus($status);
    $payment->setAmount($amount);
    $payment->setOrder($order);
    $payment->setProcessingSystem($processingSystem);

    if ($status === Payment::PAYED) {
      $payment->setBankInfo($bankInfo);
    }

    return $payment;
  }
}