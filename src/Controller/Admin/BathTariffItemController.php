<?php

namespace App\Controller\Admin;

use App\Entity\BathTariff;
use App\Entity\BathTariffItem;
use App\Entity\Category;
use App\Entity\TariffItemDay;
use App\Form\Type\BathTariffItemType;
use App\Form\Type\BathTariffType;
use App\Form\Type\CategoryType;
use App\Form\Type\TariffItemDayType;
use App\Service\TelegramService;
use Doctrine\ORM\EntityManager;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class BathTariffItemController extends AbstractController
{
  /** @var SerializerInterface $serializer */
  private $serializer;

  public function __construct(SerializerInterface $serializer, UploadableManager $uploadableManager)
  {
    $this->serializer = $serializer;
  }

  public function update(Request $request, $id, $tariffId): Response
  {
    if (!$id) {
      if (!$tariffId) {
        return new Response('Не указан id тарифа.', 400);
      }
      $tariff = $this->getDoctrine()->getRepository(BathTariff::class)->find($tariffId);

      if (!$tariff) {
        return new Response('Не найден тариф с таким id.', 404);
      }

      $tariffItem = new BathTariffItem();
      $tariffItem->setTarif($tariff);
    } else {
      /** @var BathTariffItem */
      $tariffItem = $this->getDoctrine()->getRepository(BathTariffItem::class)->find($id);
      if (!$tariffItem) {
        return $this->redirectToRoute('admin_bathtariffs');
      }
    }

    /** @var array $days*/
    $days = $request->query->get('tariff_item_day');

    $form = $this->createForm(BathTariffItemType::class, $tariffItem );
    // $form->handleRequest($request);
    $form->submit($request->query->all('bath_tariff_item'));

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      // dump($days); die();
      $tariffDays = $tariffItem->getDays();
      $tariffDayKeys = array_keys($tariffItem->getDays()->toArray());
      foreach($days as $day) {
        if (isset($day['isChecked'])) {
          $isExists = false;
          foreach($tariffDays as $tariffDay) {
            if ($tariffDay->getDay() == $day['day']) {
              $tariffDay->setPrice($day['price']*100);
              $isExists = true;
              break;
            }
          }

          if (!$isExists) {
            $newDay = new TariffItemDay();
            $newDay->setDay($day['day']);
            $newDay->setPrice(intval($day['price'])*100);
  
            $tariffItem->addDay($newDay);
          }
        }
      }
      // dump( $tariffItem->getDays()); die();
      $tariffItem->setPrice($tariffItem->getPrice()*100);
      // TODO проверки что время не пересекается итп
      if (!$tariffItem->getId()) {
        $em->persist($tariffItem);
      }
      $em->flush();

      return $this->redirect($this->generateUrl('admin_bathtariff_edit', ['id'=>$tariffItem->getId()]));
    }

    // $serialized = json_decode($this->serializer->serialize(['bathTariffItems' => $bathTariff->getBathTariffItems()], 'json', ['groups' => ['tarifItem:item']]));
    // return $this->render('admin\bathtariff\edit.html.twig', [
    //   'form' => $form->createView(),
    //   'bathTariffItems' => $serialized->bathTariffItems
    // ]);
  }

  public function getItemDays(Request $request, $id, $tariffId)
  {
    $days = [
      1 => 'Понедельник',
      2 => 'Вторник',
      3 => 'Среда',
      4 => 'Четверг',
      5 => 'Пятница',
      6 => 'Суббота',
      7 => 'Воскресенье',
    ];
    $dayForms = [];
    $tariff = null;
    if (!$id) {
      $tariff = new  BathTariffItem();
      foreach($days as $key=>$day) {
        $dayTariff = new TariffItemDay();
        $dayTariff->setDay($key);
        $form = $this->createForm(TariffItemDayType::class, $dayTariff);
        array_push($dayForms, $form->createView());
      }
      $url = $this->generateUrl('admin_tariffitem_create', ['tariffId'=>$tariffId ]);
    } else {
      $tariff = $this->getDoctrine()->getRepository(BathTariffItem::class)->findOneBy(['id' => $id]);
      $tariffDays = $tariff->getDays();

      $tariff->setPrice($tariff->getPrice()/100);
      $tariffDayKeys = array_map(fn($el) => $el->getDay(), $tariffDays->toArray());
      foreach($days as $key=>$day) {
        if (count($tariffDays)) {
          $idx = array_search($key, $tariffDayKeys);
          if (is_int($idx)) {
            $dayTariff = $tariffDays[$idx];
            $dayTariff->setPrice($dayTariff->getPrice()/100);
            $form = $this->createForm(TariffItemDayType::class, $dayTariff);
            $form->get('isChecked')->setData(true);
          } else {
            $dayTariff = new TariffItemDay();
            $dayTariff->setDay($key);
            $form = $this->createForm(TariffItemDayType::class, $dayTariff);
            $form->get('isChecked')->setData(false);
          }
          array_push($dayForms, $form->createView());
        } else {
          $dayTariff = new TariffItemDay();
          $dayTariff->setDay($key);
          $form = $this->createForm(TariffItemDayType::class, $dayTariff);
          $form->get('isChecked')->setData(false);
          array_push($dayForms, $form->createView());
        }
      }
      $url = $this->generateUrl('admin_tariffitem_update', ['id'=>$id ]);
    }

    $formBathTariff = $this->createForm(BathTariffItemType::class, $tariff);

    return $this->render('admin\bathtariff\modals\_itemdays.html.twig', [
      'formBathTariff' => $formBathTariff->createView(),
      'days' => $dayForms,
      'weekDays' => $days,
      'url' => $url,
    ]);
  }
}