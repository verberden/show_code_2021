<?php

namespace App\Controller\Api;

use App\Entity\Bathhouse;
use App\Entity\BathReservation;
use App\Entity\BathSchedule;
use App\Entity\BathSlot;
use App\Entity\Customer;
use App\Entity\Order;
use App\Helper\SlotsHelper;
use App\Repository\BathScheduleRepository;
use App\Repository\BathhouseRepository;
use App\Service\BathReservationService;
use App\Service\ImageService;
use App\Service\Sberbank;
use App\Service\TelegramService;
use DateTimeImmutable;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\BathReservationItems;
use App\Entity\BathTariff;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Category controller.
 */
final class BathScheduleController extends AbstractFOSRestController
{
  private $serializer;
  /** @var ImageService $imageService */
  private $imageService;

  /** @var Sberbank $sberbank */
  private $sberbank;

  /** @var TelegramService $telegramService */
  private $telegramService;

  /** @var BathReservationService $reservationService */
  private $reservationService;

  public function __construct(
    SerializerInterface $serializer, 
    ImageService $imageService, 
    Sberbank $sberbank, 
    TelegramService $telegramService, 
    BathReservationService $reservationService)
  {
    $this->serializer = $serializer;
    $this->imageService = $imageService;
    $this->sberbank = $sberbank;
    $this->telegramService = $telegramService;
    $this->reservationService = $reservationService;
  }

  private function getRepo(): BathScheduleRepository
  {   
    return $this->getDoctrine()->getRepository(BathSchedule::class);
  }

  /**
   * Gets a Bathhouse resource
   * @return View
   */
  public function one(string $dateString): View
  {  
    $date = new DateTimeImmutable($dateString);

    /** @var BathhouseRepository $bathhouseRepo */
    $bathhouseRepo = $this->getDoctrine()->getRepository(Bathhouse::class);

    $bathhouses = $bathhouseRepo->findWithDaySchedule($date);

    $results = [];
    // $reservedSlots = [];
    foreach($bathhouses as $bathhouse) {
      $reservedSlots = [];
      $bathSchedule = $this->getDoctrine()->getRepository(BathSchedule::class)->findOneBy(['bathhouse' => $bathhouse, 'date' => $date]);
      if ($bathSchedule) {
        $existedSlots = $bathSchedule->getBathSlots();
        foreach($existedSlots as $slot) {
          if ($slot->getIsReserved()) {
            array_push($reservedSlots, $slot->getTime());
          }
        }
      }
  
      $slotTime = $bathhouse->getSlotTime();
      $startTime = '09:00';
      
      /** @var BathTariff[]|null $tariffs */
      $tariffs = $this->getDoctrine()->getRepository(BathTariff::class)->findActiveTariffs($bathhouse->getId(), $date);

      $tariff = null;
      $tariffItems = [];
      if ($tariffs && count($tariffs)) {
        $tariff = $tariffs[0];
        /** @var BathTariffItem[] $tariffItems */
        $tariffItems = $tariff->getBathTariffItems();
      }

      $slots = SlotsHelper::generateSlots($dateString, $startTime, $slotTime, $tariffItems, $reservedSlots);
      $isCloseForService = $bathSchedule ? $bathSchedule->getCloseForService() : false;
      array_push($results, [
        'id' => $bathhouse->getId(),
        'image' => $bathhouse->getImage() ? 
          [
            'id' => $bathhouse->getImage()->getId(),
            'path' => $bathhouse->getImage()->getPath(),
            'filename' => $bathhouse->getImage()->getFilename(), 
          ] : [],
        'name' => $bathhouse->getName(),
        'slots' => $isCloseForService ? [] : $slots,
        'closeForService' => $isCloseForService,
      ]);
    }

    $serialized = json_decode($this->serializer->serialize($results, 'json'));
    $this->imageService->buildManyImageWithCache($serialized);
    return View::create($serialized, Response::HTTP_OK);
  }

  /**
   * Gets a Categories resources
   * @return View
   */
  public function create(Request $request, $bathId, $date): View
  {
    $data = json_decode($request->getContent(), true);
    $time = isset($data['time']) ? $data['time'] : [];
    if (!isset($time[0])) {
      return View::create('Не указаны слоты.', Response::HTTP_BAD_REQUEST);
    }
    [$startTime] = explode('-', $time[0]);
    if (new DateTimeImmutable($date.' '.$startTime) < new DateTimeImmutable()) {
      return View::create('Дата бронирования меньше текущей.', Response::HTTP_BAD_REQUEST);
    }

    $previousTime = '';
    foreach($time as $idx=>$slotTime) {
      if ($idx === 0) {
        [,$previousTime] = explode('-', $slotTime);
      } else {
        [$startTime] = explode('-', $slotTime);
        if ($previousTime !== $startTime) {
          return View::create('Временные слоты выбраны не подряд.', Response::HTTP_BAD_REQUEST);
        }
      }

    }

    $stopwatch = new Stopwatch();
    $stopwatch->start('all');    

    $customerPhone = $data['customer']['phone'];
    $customerName = $data['customer']['name'];
    $additionalServices = $data['services'] ?? null;

    /** @var BathhouseRepository $bathhouseRepo */
    $bathhouseRepo = $this->getDoctrine()->getRepository(Bathhouse::class);

    $bathhouse = $bathhouseRepo->findOneBy(['id' => $bathId]);
    
    if (!$bathhouse) {
      throw new NotFoundHttpException('Баня не найдена.');
    }

    $bathhouseId = $bathhouse->getId();
    /** @var BathSchedule|null $schedule */
    $schedule = $this->getDoctrine()->getRepository(BathSchedule::class)->findOneBy(['bathhouse' => $bathhouseId, 'date' => new DateTimeImmutable($date)]);

    if (!$schedule) {
      $schedule = new BathSchedule();
      $schedule->setDate(new DateTimeImmutable($date));
      $schedule->setBathhouse($bathhouse);
    }

    $bathSlots = $schedule->getBathSlots();

    $customerPhone = (int) filter_var($customerPhone, FILTER_SANITIZE_NUMBER_INT);
    /** @var Customer[]|null $customer */
    $result = $this->getDoctrine()->getRepository(Customer::class)->findPhoneLike($customerPhone);

    $customer = $result && count($result) ? $result[0] : null;

    if (!$customer) {
      $customer = new Customer();
      $customer->setPhone($customerPhone);
      $customer->setName($customerName);
    }
    $bathReservation = new BathReservation();
    $bathReservation->setStatus(Order::NEW_STATUS);
    $bathReservation->setCustomer($customer);
    $bathReservation->setDate(new DateTimeImmutable($date));
    $bathReservation->setCustomerName($customerName);
    $bathReservation->setCustomerPhone($customerPhone);
    $bathReservation->setBathName($bathhouse->getName());
    $reservationCost = 0;
    /** @var BathTariff[]|null $tariffs */
    $tariffs = $this->getDoctrine()->getRepository(BathTariff::class)->findActiveTariffs($bathId, new DateTimeImmutable($date));

    $tariff = null;
    $tariffItems = [];
    if ($tariffs && count($tariffs)) {
      $tariff = $tariffs[0];
      /** @var BathTariffItem[] $tariffItems */
      $tariffItems = $tariff->getBathTariffItems();
    }

    $times = [];
    $slots = SlotsHelper::generateSlots($date, '09:00', $bathhouse->getSlotTime(), $tariffItems);
    foreach($slots as $slot) {
      $times[$slot['time']] = [
        'price' => $slot['price']
      ];
    }

    if ($bathSlots && count($bathSlots)) {
      foreach($bathSlots as &$bathSlot) {
        if (in_array($bathSlot->getTime(), $time) && !$bathSlot->getIsReserved()) {
          $bathSlot->setIsReserverd(true);
          $bathSlot->setCustomer($customer);
          $bathSlot->setPrice($times[$bathSlot->getTime()]['price']);
          $bathReservation->addSlot($bathSlot);
          $reservationCost += $times[$bathSlot->getTime()]['price'];
          $time = array_diff($time, [$bathSlot->getTime()]);
          if (count($time) === 0) {
            break;
          }
        } else if (in_array($bathSlot->getTime(), $time) && $bathSlot->getIsReserved()) {
          throw new NotFoundHttpException('Слот со временем '. $bathSlot->getTime(). ' уже забронирован.');
        }
      }
    }

    // $stopwatch->start('slots');
    if (count($time)) {
      $timeValues = array_keys($times);

      foreach($time as $t) {
        if (!in_array($t, $timeValues)){
          throw new NotFoundHttpException('Не найдено указанное время сеанса '.$t);
        }
        $bathSlot = new BathSlot();
        $bathSlot->setTime($t);
        $bathSlot->setIsReserved(true);
        $bathSlot->setCustomer($customer);
        $bathSlot->setPrice($times[$bathSlot->getTime()]['price']);
        $reservationCost += $times[$t]['price'];
        $bathReservation->addSlot($bathSlot);
        $schedule->addBathSlot($bathSlot);
      }
    }
    // $eventSlots = $stopwatch->stop('slots');

    // $stopwatch->start('services');
    /** @var BathReservationItems[] $additionalItems */
    $additionalItems = $this->reservationService->createReservationItems($additionalServices);

    foreach($additionalItems as $item) {
      $bathReservation->addAdditionalServiceItem($item);
      $reservationCost += $item->getCost();
    }
    // $eventServices = $stopwatch->stop('services');

    $em = $this->getDoctrine()->getManager();

    $bathReservation->setCost($reservationCost);
    if (!$schedule->getId()) {
      $em->persist($schedule);
    }

    $em->persist($bathReservation);
    $em->flush();

    if (isset($data['payWithApple'])) {
      $processingSystem = Sberbank::APPLE_PROCESSING;
    } else if (isset($data['payWithGoogle'])) {
      $processingSystem = Sberbank::GOOGLE_PROCESSING;
    } else {
      $processingSystem = Sberbank::APP_PROCESSING;
    }

    $stopwatch->start('other');
    $url = $this->sberbank->init($bathReservation, $bathReservation->getCost(), 'Заказ бани №'.$schedule->getId(), $processingSystem, false, 'bath_payment');
    $eventOther = $stopwatch->stop('other');
    $serialized = json_decode($this->serializer->serialize(['reservation' => $bathReservation, 'url' => $url], 'json', ['groups' => ['schedule:item']]));

    // $this->telegramService->sendToAllAdmins('Поступил новый заказ: '.$this->generateUrl('admin_order_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
    $eventAll = $stopwatch->stop('all');
    $serialized->allDuration = $eventAll->getDuration();
    $serialized->sbernankSendDuration = $eventOther->getDuration();

    return View::create($serialized, Response::HTTP_OK);
  }
}