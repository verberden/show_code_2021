<?php

namespace App\Service;

use App\Service\Payment\BathPaymentService;
use App\Service\Payment\OrderPaymentService;

class PaymentService {

  /** @var OrderPaymentService $orderPaymentService */
  private $orderPaymentService;

  /** @var BathPaymentService $bathPaymentService */
  private $bathPaymentService;

  public function __construct(
    OrderPaymentService $orderPaymentService,
    BathPaymentService $bathPaymentService)
  {
    $this->orderPaymentService = $orderPaymentService;
    $this->bathPaymentService = $bathPaymentService;
  }

  public function init($paymentType = 'order_payment')
  {
    switch ($paymentType) {
      case 'bath_payment':
        $service = $this->bathPaymentService;
        break;
      default:
        $service = $this->orderPaymentService;
    }

    return $service;
  }
}