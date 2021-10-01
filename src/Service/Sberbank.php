<?php

namespace App\Service;

use App\Entity\BathPayment;
use App\Entity\BathReservation;
use App\Entity\Payment;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Voronkovich\SberbankAcquiring\Client;
use Voronkovich\SberbankAcquiring\Currency;
use Voronkovich\SberbankAcquiring\OrderStatus;

// https://securepayments.sberbank.ru/wiki/doku.php/integration:api:start
class Sberbank
{
  const PAYMENT_SYSTEM_CODE = 'sber';
  const APP_PROCESSING = 'app';
  const APPLE_PROCESSING = 'apple';
  const GOOGLE_PROCESSING = 'google';

  const PROCESSING_SYSTEM_TEXT = [
      self::APPLE_PROCESSING => "ApplePay",
      self::GOOGLE_PROCESSING => "GooglePay",
  ];

  private $test;
  private $login;
  private $pass;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var Container
   */
  private $container;

  /** @var \Symfony\Bridge\Monolog\Logger $logger */
  private $logger;

  public function __construct(ContainerInterface $container, LoggerInterface $paymentLogger)
  {
    $this->container = $container;
    $this->httpClient = $this->container->get('eight_points_guzzle.client.my_client');
    $this->test = $this->container->getParameter('sberbank_test');
    $this->login = $this->container->getParameter('sberbank_login');
    $this->pass = $this->container->getParameter('sberbank_pass');
    $this->logger = $paymentLogger;
  }

  private function initOrderPayments($order, $amount, $description, $processingSystem = self::APP_PROCESSING, $email = false) {
    $paymentType = 'order_payment';
    /** @var Client $sberbank */
    $sberbank = $this->getClient();

    /** @var EntityManager $em */
    $em = $this->container->get('doctrine.orm.entity_manager');

    /** @var Payment $paymentEntity */
    $paymentEntity = $em->getRepository(Payment::class)
        ->findOneBy(['orderId' => $order->getId(), 'amount' => $amount, 'status' => Payment::WAIT], ['createdAt' => 'DESC']);


    if ($paymentEntity && $paymentEntity->getUrl() && $paymentEntity->getSystemId()) {
      try {
        $status = $sberbank->getOrderStatus($paymentEntity->getSystemId());
        if (OrderStatus::isCreated($status['orderStatus']) || OrderStatus::isDeposited($status['orderStatus'])) {
          return $paymentEntity->getUrl();
        }
      } catch (\Exception $exception) {
        // тут обработка ошибки сбербанка
        $this->logger->error("init Sberbank. problem with getting order status for orderId = " . $order->getId() . " type=" . $paymentType . ' exception_error=' . $exception->getMessage());
      }
    }

    $uuid = 'app_'.bin2hex(random_bytes(10));
    /** @var Router $router */
    $router = $this->container->get('router');
    $successUrl = $router->generate('api_sberbank_sucess', ['payment_type' => $paymentType], UrlGeneratorInterface::ABSOLUTE_URL);
    $failUrl = $router->generate('api_sberbank_fail', ['payment_type' => $paymentType], UrlGeneratorInterface::ABSOLUTE_URL);

    // $orderItems = $order->getOrderItems();
    // $items = [];
    // foreach ($orderItems as $key => $orderItem) {
    //   array_push($items, [
    //       'positionId' => $orderItem->getId(),
    //       'name' => $orderItem->getName(),
    //       'quantity' => [
    //           'value' => $orderItem->getQuantity(),
    //           'measure' => 'шт'
    //       ],
    //       'itemAmount' => $orderItem->getCost(),
    //       'itemCode' => $orderItem->getId(),
    //       'itemPrice' => $orderItem->getPrice()
    //   ]);
    // }

    // $orderBundle = [
    //     'cartItems' => [
    //         'items' => $items
    //     ]
    // ];

    $params = [
        'currency' => Currency::RUB,
        'failUrl' => $failUrl,
      // выпилил передачу корзины, чтобы делать частичный возврат
      // 'orderBundle' => $orderBundle
    ];
    if ($email) {
      $params['email'] = $email;
    }

    if ($description) {
      $params['description'] = mb_substr($description, 0, 511);
    }

    try {

      if ($processingSystem === self::APP_PROCESSING) {
        $result = $sberbank->registerOrder(
            $uuid,
            $amount,
            $successUrl,
            $params
        );

        if ($result['orderId'] && $result['formUrl']) {
          /** @var Payment $paymentNew */
          $paymentNew = new Payment();
          $paymentNew->setSystemId($result['orderId']);
          $paymentNew->setPaymentSystem(self::PAYMENT_SYSTEM_CODE);
          $paymentNew->setUrl($result['formUrl']);
          $paymentNew->setStatus(Payment::WAIT);
          $paymentNew->setAmount($amount);
          $paymentNew->setOrder($order);
          $paymentNew->setProcessingSystem(self::APP_PROCESSING);
          $em->persist($paymentNew);
          $em->flush();

          return $result['formUrl'];
        }
      }

    } catch (\Exception $exception) {
      $this->logger->error("init Sberbank not register order for reservationId = " . $order->getId() . " type=" . $paymentType . ' exception_error=' . $exception->getMessage());
    }
    // если какая-то ошибка, то ссылки нет
    return null;
  }

  private function initBathPayments(BathReservation $reservation, $amount, $description, $processingSystem = self::APP_PROCESSING, $email = false) {
    $paymentType = 'bath_payment';
    /** @var Client $sberbank */
    $sberbank = $this->getClient();

    /** @var EntityManager $em */
    $em = $this->container->get('doctrine.orm.entity_manager');

    // $stopwatch = new Stopwatch();
    // $stopwatch->start('other');
    /** @var Payment $paymentEntity */
    $paymentEntity = $em->getRepository(BathPayment::class)
        ->findOneBy(['bathReservation' => $reservation->getId(), 'amount' => $amount, 'status' => Payment::WAIT], ['createdAt' => 'DESC']);

    if ($paymentEntity && $paymentEntity->getUrl() && $paymentEntity->getSystemId()) {
      try {
        $status = $sberbank->getOrderStatus($paymentEntity->getSystemId());
        if (OrderStatus::isCreated($status['orderStatus']) || OrderStatus::isDeposited($status['orderStatus'])) {
          return $paymentEntity->getUrl();
        }
      } catch (\Exception $exception) {
        // тут обработка ошибки сбербанка
        $this->logger->error("init Sberbank. problem with getting order status for reservationId = " . $reservation->getId() . " type=" . $paymentType . ' exception_error=' . $exception->getMessage());
      }
    }

    $uuid = 'app_'.bin2hex(random_bytes(10));
    /** @var Router $router */
    $router = $this->container->get('router');
    $successUrl = $router->generate('api_sberbank_sucess', ['payment_type' => $paymentType], UrlGeneratorInterface::ABSOLUTE_URL);
    $failUrl = $router->generate('api_sberbank_fail', ['payment_type' => $paymentType], UrlGeneratorInterface::ABSOLUTE_URL);

    $params = [
        'currency' => Currency::RUB,
        'failUrl' => $failUrl,
      // выпилил передачу корзины, чтобы делать частичный возврат
      // 'orderBundle' => $orderBundle
    ];
    if ($email) {
      $params['email'] = $email;
    }

    if ($description) {
      $params['description'] = mb_substr($description, 0, 511);
    }

    try {
      if ($processingSystem === self::APP_PROCESSING) {
        $params['sessionTimeoutSecs'] = BathReservation::PAYMENT_LIFETIME_MINUTES * 60;
        $result = $sberbank->registerOrder(
            $uuid,
            $amount,
            $successUrl,
            $params
        );

        // dump($event->getDuration(), $params); die();
        if ($result['orderId'] && $result['formUrl']) {
          /** @var BathPayment $paymentNew */
          $paymentNew = new BathPayment();
          $paymentNew->setSystemId($result['orderId']);
          $paymentNew->setPaymentSystem(self::PAYMENT_SYSTEM_CODE);
          $paymentNew->setUrl($result['formUrl']);
          $paymentNew->setStatus(Payment::WAIT);
          $paymentNew->setAmount($amount);
          $paymentNew->setBathReservation($reservation);
          $paymentNew->setProcessingSystem(self::APP_PROCESSING);
          $em->persist($paymentNew);
          $em->flush();

          return $result['formUrl'];
        }
      }

    } catch (\Exception $exception) {
      $this->logger->error("init Sberbank not register order for reservationId = " . $reservation->getId() . " type=" . $paymentType . ' exception_error=' . $exception->getMessage());
    }
    // если какая-то ошибка, то ссылки нет
    return null;
  }


  public function init($entity, $amount, $description, $processingSystem = self::APP_PROCESSING, $email = false, $paymentType = 'order_payment')
  {
    switch ($paymentType) {
      case 'bath_payment':
        return $this->initBathPayments($entity, $amount, $description, $processingSystem, $email);
        break;
      default:
        return $this->initOrderPayments($entity, $amount, $description, $processingSystem, $email);
    } 
  }

  public function refundOrder($orderId, $amountToRefund = 0, $params = ['refundItems' => []])
  {
    /** @var Client $sberbank */
    $sberbank = $this->getClient();

    $result = $sberbank->refundOrder($orderId, $amountToRefund, $params);

    return $result;
  }

  public function getOrderStatus($systemId)
  {
    /** @var Client $sberbank */
    $sberbank = $this->getClient();

    try {
      $result = $sberbank->getOrderStatus($systemId);
      $this->logger->info('Sberbank Service info getOrderStatus, systemId='.$systemId.' result='.json_encode($result));
    } catch (\Exception $e) {
      $this->logger->error('Sberbank Service Error in getOrderStatus, code='.$e->getCode().' message='.$e->getMessage());
    }

    return $result;
  }

  private function getPaymentUrl()
  {
    if ($this->test) {
      return Client::API_URI_TEST;
    } else {
      return Client::API_URI;
    }
  }

  public function getClient()
  {
    if (!$this->login || !$this->pass) {
      $this->logger->error("getClient Sberbank not work. because login or password is clean");
      return null;
    }
    $sberbank = new Client([
        'userName' => $this->login,
        'password' => $this->pass,
      // A language code in ISO 639-1 format.
      // Use this option to set a language of error messages.
        'language' => 'ru',

      // A currency code in ISO 4217 format.
      // Use this option to set a currency used by default.
        'currency' => Currency::RUB,

      // An uri to send requests.
      // Use this option if you want to use the Sberbank's test server.
        'apiUri' => $this->getPaymentUrl(),

      // An HTTP client for sending requests.
      // Use this option when you don't want to use
      // a default HTTP client implementation distributed
      // with this package (for example, when you have'nt
      // a CURL extension installed in your server).
//        'httpClient' => new GuzzleAdapter(new \GuzzleHttp\Client()),
    ]);
    return $sberbank;
  }

  public function payWith(string $processingSystem, $data): array
  {
    $paramsData = [];

    if ($processingSystem
        && in_array($processingSystem, [self::APPLE_PROCESSING, self::GOOGLE_PROCESSING])
        && isset($data['orderId']) && isset($data['merchant']) && isset($data['paymentToken'])) {
      if ($processingSystem === self::APPLE_PROCESSING) {
        $sberbank = $this->getClient();
        $paramsData['description'] = isset($data['description']) ? $data['description'] : '';

        $this->logger->info('Try payWith ApplePay for data: ' . json_encode($data) . '  paramsData=' . json_encode($paramsData) . '.');
        $result = $sberbank->payWithApplePay(
            $data['orderId'],
            $data['merchant'],
            $data['paymentToken'],
            $paramsData
        );
        $this->logger->info('Result payWith ApplePay: ' . json_encode($result));
        return $result;
      } else if ($processingSystem === self::GOOGLE_PROCESSING && isset($data['amount'])) {
        /** @var Router $router */
        $router = $this->container->get('router');

        $paramsData = [
            ...$paramsData,
            'description' => $data['description'] ? $data['description']:'',
            'amount' => $data['amount'],
            'preAuth' => false,
            'returnUrl' => $router->generate('api_sberbank_sucess', ['payment_type' => $processingSystem], UrlGeneratorInterface::ABSOLUTE_URL),
            'failUrl' => $router->generate('api_sberbank_fail', ['payment_type' => $processingSystem], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $this->logger->info('Try payWith GooglePay for data: ' . json_encode($data) . '  paramsData=' . json_encode($paramsData) . '.');

        $sberbank = $this->getClient();
        $result = $sberbank->payWithGooglePay(
            $data['orderId'],
            $data['merchant'],
            $data['paymentToken'],
            $paramsData
        );

        $this->logger->info('Result payWith GooglePay: ' . json_encode($result));

        return $result;
      }
    }
  }

  public function getAcsUrlForGoogle(string $systemId)
  {
    return $this->getPaymentUrl().'/payment/acsRedirect.do?orderId='.$systemId;
  }
}
