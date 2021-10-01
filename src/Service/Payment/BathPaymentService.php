<?php

namespace App\Service\Payment;

use App\Entity\BathPayment;
use App\Entity\Payment;
use App\Repository\BathPaymentRepository;
use App\Repository\BathReservationRepository;
use App\Service\Sberbank;

class BathPaymentService extends AbstractPaymentServiceClass {

  // /** @var OrderService $orderService */
  // private $orderService;

  // /** @var PaymentRepository $paymentRepository */
  // private $paymentRepository;

  /** @var BathReservationRepository $bathReservationRepository */
  private $bathReservationRepository;
  
  /** @var BathPaymentRepository $bathPaymentRepository */
  private $bathPaymentRepository;

  public function __construct(
    BathReservationRepository $bathReservationRepository,
    BathPaymentRepository $bathPaymentRepository)
  {
    $this->bathReservationRepository = $bathReservationRepository;
    $this->bathPaymentRepository = $bathPaymentRepository;
  }

  public function getOrder(string $systemId)
  {
    return $this->bathReservationRepository->findReservationByPaymentId($systemId);
  }

  public function getOrderByHash(string $hash)
  {
    return $this->bathReservationRepository->findOneBy(["hash" => $hash]);
  }

  public function getPayment(string $systemId) {

    return $this->bathPaymentRepository->findOneBy(['systemId' => $systemId]);
  }

  public function generateSuccesPayMessage(string $orderId) {
    return 'Оплачено резервирование бани №' . $orderId . '.';
  }

  public function generatePayment(string $orderId, string $status, int $amount, $bathReservation, string $processingSystem, ?string $url, $paymentSystemCode = Sberbank::PAYMENT_SYSTEM_CODE, $bankInfo = null)
  {
    /** @var BathPayment $payment */
    $payment = new BathPayment();
    $payment->setSystemId($orderId);
    $payment->setPaymentSystem($paymentSystemCode);
    $payment->setUrl($url);
    $payment->setStatus($status);
    $payment->setAmount($amount);
    $payment->setBathReservation($bathReservation);
    $payment->setProcessingSystem($processingSystem);

    if ($status === Payment::PAYED) {
      $payment->setBankInfo($bankInfo);
    }

    return $payment;
  }
}