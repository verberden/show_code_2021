<?php
namespace App\Controller\Admin;

use App\Entity\Bathhouse;
use App\Entity\BathSchedule;
use App\Entity\BathSlot;
use App\Entity\Customer;
use App\Form\Type\BathhouseType;
use App\Helper\SlotsHelper;
use App\Repository\BathhouseRepository;
use DateTimeImmutable;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BathhouseController extends AbstractController
{

    /** @var UploadableManager $uploadableManager  */
    private $uploadableManager;

    public function __construct(UploadableManager $uploadableManager)
    {
      $this->uploadableManager = $uploadableManager;
    }
  private function getRepo(): BathhouseRepository
  {
    return $this->getDoctrine()->getRepository(Bathhouse::class);
  }

  public function index()
  {
    $bathhouses = $this->getRepo()->findAll();

    return $this->render('admin\bathhouse\index.html.twig', [
      'bathhouses' => $bathhouses
    ]);
  }

  public function create(Request $request)
  {
    $bathhouse = new Bathhouse();

    $form = $this->createForm(BathhouseType::class, $bathhouse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      if ($bathhouse->getImage() && $bathhouse->getImage()->getFile()) {
        $bathhouse->getImage()->setUploadPath('media/bathhouse_image');
        $this->uploadableManager->markEntityToUpload($bathhouse->getImage(), $bathhouse->getImage()->getFile());
      }

      $pathPhotoOther = 'media/bathhouse_image/other';
      foreach ($bathhouse->getGalleries() as $photo) {
        if ($photo->getImage()->getFile()) {
          $photo->getImage()->setUploadPath($pathPhotoOther);
          $this->uploadableManager->markEntityToUpload($photo->getImage(), $photo->getImage()->getFile());
        }
      }

      if (!$bathhouse->getId()) {
        $em->persist($bathhouse);
      }

      $em->flush();

      return new Response($this->generateUrl('admin_bathhouses', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
    // die();
    return $this->render('admin\bathhouse\edit.html.twig', [
        'form' => $form->createView()
    ]);
  }

  public function update(Request $request, $id)
  {
    $bathhouse = $this->getRepo()->findOneBy(['id' => $id]);
    $form = $this->createForm(BathhouseType::class, $bathhouse);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      // dump($bathhouse->getBathTariffs()[0]);die();
      if ($bathhouse->getImage() && $bathhouse->getImage()->getFile()) {
        $bathhouse->getImage()->setUploadPath('media/bathhouse_image');
        $this->uploadableManager->markEntityToUpload($bathhouse->getImage(), $bathhouse->getImage()->getFile());
      }

      $pathPhotoOther = 'media/bathhouse_image/other';
      foreach ($bathhouse->getGalleries() as $photo) {
        if ($photo->getImage()->getFile()) {
          $photo->getImage()->setUploadPath($pathPhotoOther);
          $this->uploadableManager->markEntityToUpload($photo->getImage(), $photo->getImage()->getFile());
        }
      }

      $em->flush();

      return new Response($this->generateUrl('admin_bathhouses', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    return $this->render('admin\bathhouse\edit.html.twig', [
      'form' => $form->createView()
    ]);
  }

  public function createReservation(Request $request, int $id, string $date)
  {
    $bathhouse = $this->getRepo()->findOneBy(['id'=>$id, 'enabled' => true]);
    $time = $request->get('bathslot');

    $customer = $request->get('customer');
    $customerPhone = $customer['phone'];
    $customerName = $customer['name'];

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

    /** @var Customer[]|null $customer */
    $result = $this->getDoctrine()->getRepository(Customer::class)->findPhoneLike($customerPhone);

    $customer = $result && count($result) ? $result[0] : null;

    if (!$customer) {
      $customer = new Customer();
      $customer->setPhone($customerPhone);
      $customer->setName($customerName);
    }

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
          $time = array_diff($time, [$bathSlot->getTime()]);
          if (count($time) === 0) {
            break;
          }
        } else {
          throw new NotFoundHttpException('Слот со временем '. $bathSlot->getTime(). ' уже забронирован.');
        }
      }
    } 

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
        $bathSlot->setReservedByAdmin(true);
  
        $schedule->addBathSlot($bathSlot);
      }
    } 

    $em = $this->getDoctrine()->getManager();

    if (!$schedule->getId()) {
      $em->persist($schedule);
    }

    $em->flush();

    return new JsonResponse(['status' => 'ok']);
  }

  public function toggleForService(int $id, string $date)
  {
    $bathhouse = $this->getRepo()->findOneBy(['id'=>$id, 'enabled' => true]);
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
    } else {
      /** @var BathSlots[] $bathSlots */
      $bathSlots = $schedule->getBathSlots();
      foreach($bathSlots as $bathSlot) {
        if ($bathSlot->getBathReservation()->getPaymentSum()) {
          return new JsonResponse(['status' => 'error', 'meassage' => 'Есть оплаченный слот.'], 400);
        }
      }
    }
    $schedule->setCloseForService(!$schedule->getCloseForService());

    $em = $this->getDoctrine()->getManager();

    if (!$schedule->getId()) {
      $em->persist($schedule);
    }

    $em->flush();

    return new JsonResponse(['status' => 'ok']);
  }
}