<?php
namespace App\Controller\Admin;

use App\Entity\Bathhouse;
use App\Entity\Bathschedule;
use App\Entity\BathSlot;
use App\Entity\BathTariff;
use App\Entity\Customer;
use App\Entity\Order;
use App\Helper\SlotsHelper;
use App\Repository\BathscheduleRepository;
use DateTime;
use DateTimeImmutable;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BathscheduleController extends AbstractController
{
  /** @var UploadableManager $uploadableManager  */
  private $uploadableManager;

  public function __construct(UploadableManager $uploadableManager)
  {
    $this->uploadableManager = $uploadableManager;
  }
  
  private function getRepo(): BathscheduleRepository
  {
    return $this->getDoctrine()->getRepository(Bathschedule::class);
  }

  private function _generateClass(?BathSlot $slot): string
  {
    if (!$slot) {
      return "m-fc-event--danger m-fc-event--solid-danger";
    }

    if ($slot->getIsReserved() && ($slot->getBathReservation() && $slot->getBathReservation()->getStatus() === Order::PAYED_STATUS)) {
      return "m-fc-event--success m-fc-event--solid-success";
    }

    if ($slot->getReservedByAdmin()) {
      return "m-fc-event--metal m-fc-event--solid-metal";
    }

    return "m-fc-event--warning m-fc-event--solid-warning";
  }

  public function index()
  {
    /** @var Bathhouse[]|null $bathhouses */
    $bathhouses = $this->getDoctrine()->getRepository(Bathhouse::class)->findBy(['enabled' => true]);
   
    $schedules = [];
    foreach($bathhouses as $bathhouse) {
      $schedules[$bathhouse->getId()] = [];
      /** @var Collection|BathSchedule[] $bathSchedules */
      $bathSchedules = $bathhouse->getBathSchedules();

      foreach($bathSchedules as $schedule) {
        $date = ($schedule->getDate())->format('Y-m-d');
        if (!$schedule->getCloseForService()) {
          /** @var Collection|BathSlot[] $slots */
          $slots = $schedule->getBathSlots();
          foreach($slots as $slot) {
            [$timeStart, $timeEnd] = explode('-', $slot->getTime());
            [$customerName, $cusomerPhone] = $slot->getCustomer() ? [$slot->getCustomer()->getName(), $slot->getCustomer()->getPhone()] : ['', 'не указан'];

            array_push($schedules[$bathhouse->getId()], [
              'title' => $slot->getTime().' '.$customerName,
              'start' => $date.' '.$timeStart,
              'end' => $date.' '.$timeEnd,
              'description' => $cusomerPhone,
              'className' => $this->_generateClass($slot),
            ]);
          }
        } else {
          array_push($schedules[$bathhouse->getId()], [
            'title' => 'Техническое обслуживание',
            'start' => $date.' 00:00',
            'end' => $date.' 24:00',
            'className' => $this->_generateClass(null),
          ]);
        }
      }
    }

    return $this->render('admin\bathschedule\index.html.twig', [
      'schedules' => $schedules,
      'bathhouses' => $bathhouses
    ]);
  }

  public function getSlotsSchedule(Request $reqest, string $date)
  {
    $bathhouses = $this->getDoctrine()->getRepository(Bathhouse::class)->findBy(['enabled' => true]);

    if (!$bathhouses || count($bathhouses) < 1) {
      return new Response('Не найдены доступные бани.', 404);
    }

    $bathSlots = [];
    foreach ($bathhouses as $bathhouse) {
      /** @var BathSchedule|null */
      $bathSchedule = $this->getDoctrine()->getRepository(BathSchedule::class)->findOneBy(['date' => new DateTimeImmutable($date), 'bathhouse' => $bathhouse]);

      $dates = [];
      if ($bathSchedule === null || !$bathSchedule->getCloseForService()) {
        $reservedSlots = [];
        if ($bathSchedule) {
          $existedSlots = $bathSchedule->getBathSlots();
          foreach($existedSlots as $slot) {
            if ($slot->getIsReserved()) {
              array_push($reservedSlots, $slot) ; //$slot->getTime()
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
        $slots = SlotsHelper::generateAdminFrontSlots($date, $startTime, $slotTime, $tariffItems, $reservedSlots);

        $bathSlots[]= [
          'name' => $bathhouse->getName(),
          'slots' => $slots,
        ];
      }
    }

    return new JsonResponse(array('results' => $bathSlots, 'count' => count($bathhouses) ));
  }

  public function findCustomerByPhone(Request $request) 
  {
    $q = $request->get('q');

    if ($q) {
      $customers = $this->getDoctrine()->getRepository(Customer::class)->findPhoneLike($q);

      $results = array_map(
        function ($customer)
        {
          return [
            'name' => $customer->getName(),
            'value' => $customer->getPhone(),
            'label' => $customer->getPhone(),
          ];
        }, 
        $customers
      );

      return new JsonResponse(['results' => $results]);
    }

    return new JsonResponse(['results' => []]);
  }
}