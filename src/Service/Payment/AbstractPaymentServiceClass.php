<?php
namespace App\Service\Payment;

abstract class AbstractPaymentServiceClass
{
  public $order;
  public $payment;
  /* Данный метод должен быть определён в дочернем классе */
  abstract public function getOrder(string $systemId);
  abstract public function getOrderByHash(string $hash);
  abstract public function getPayment(string $systemId);
  abstract public function generateSuccesPayMessage(string $orderId);
  abstract public function generatePayment(string $orderId, string $status, int $amount, $entity, string $processingSystem, ?string $url, string $paymentSystemCode, $bankInfo = null);

  /* Общий метод */
  // public function setPaymentPayedStatus(string $orderId, string $paymentType, $bankInfo) {
  //   $payment = $this->getPayment($orderId, $paymentType);
  //   if ($payment->getStatus() != Payment::PAYED) {
  //     $payment->setStatus(Payment::PAYED);
  //     $payment->setBankInfo($bankInfo);
  //     $payment->setAmount($bankInfo['amount']);
  //   }

  //   return $payment;
  // }


}