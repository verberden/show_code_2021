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

class BathTariffController extends AbstractController
{
  /** @var SerializerInterface $serializer */
  private $serializer;

  /**  */
  private $uploadableManager;

  public function __construct(SerializerInterface $serializer, UploadableManager $uploadableManager, TelegramService $telegamService)
  {
    $this->serializer = $serializer;
    $this->uploadableManager = $uploadableManager;
    $this->telegamService = $telegamService;
  }
  
  public function index(): Response
  {
    $tariffs = $this->getDoctrine()->getRepository(BathTariff::class)->findAll();

    return $this->render('admin/bathtariff/index.html.twig', [
      'tariffs' => $tariffs,
    ]);
  }

  public function create(Request $request): Response
  {
    $bathTariff = new BathTariff();

    $form = $this->createForm(BathTariffType::class, $bathTariff);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      foreach($bathTariff->getBathTariffItems() as &$item) {
        $item->setPrice($item->getPrice()*100);
      }

      $em->persist($bathTariff);

      $em->flush();

      return $this->redirect($this->generateUrl('admin_bathtariffs'));
    }
    
    return $this->render('admin\bathtariff\edit.html.twig', [
      'form' => $form->createView()
    ]);
  }

  public function update(Request $request, $id): Response
  {
    /** @var BathTariff */
    $bathTariff = $this->getDoctrine()->getRepository(BathTariff::class)->find($id);
    if (!$bathTariff) {
      return $this->redirectToRoute('admin_bathtariffs');
    }

    foreach($bathTariff->getBathTariffItems() as &$item) {
      $item->setPrice($item->getPrice()/100);
    }

    $form = $this->createForm(BathTariffType::class, $bathTariff);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      foreach($bathTariff->getBathTariffItems() as &$item) {
        $item->setPrice($item->getPrice()*100);
      }
      // TODO проверки что время не пересекается итп
      $em->flush();

      return $this->redirect($this->generateUrl('admin_bathtariffs'));
    }

    $serialized = json_decode($this->serializer->serialize(['bathTariffItems' => $bathTariff->getBathTariffItems()], 'json', ['groups' => ['tarifItem:item']]));
    return $this->render('admin\bathtariff\edit.html.twig', [
      'form' => $form->createView(),
      'bathTariffItems' => $serialized->bathTariffItems
    ]);
  }

  public function delete($id)
  {
    $category = $this->getDoctrine()->getRepository(Category::class)->find($id);
    // TODO: пересчёт номеров позиций
    $em = $this->getDoctrine()->getManager();

    $em->remove($category);
    $em->flush();

    return $this->redirect($this->generateUrl('admin_categories'));
  }

  private function getEntityManager(): EntityManager
  {
    return $this->getDoctrine()->getManager();
  }

  public function changeStatus(Request $request): Response {
      $ids = (array) $request->request->get('ids');
      $enabled = filter_var($request->request->get('enabled'), FILTER_VALIDATE_BOOLEAN);
      if (!$ids || !count($ids) || $enabled === null || $enabled === '') {
        return new Response('Не указан один из обязательных параметров.', 404);
      }
      $em = $this->getEntityManager();
      $batchSize = 20;
      $i = 1;
      $q = $em->createQuery('select c from App\Entity\Category c WHERE c.id IN (:ids)')->setParameter('ids', $ids);
      foreach ($q->toIterable() as $category) {
          $category->setEnabled($enabled);
          ++$i;
          if (($i % $batchSize) === 0) {
              $em->flush();
              $em->clear();
          }
      }
      $em->flush();
      return new Response('Success', 200);
    }

  public function positions(Request $request)
  {
    $positions = $request->request->get('positions');
    if ($positions) {
        $this->getDoctrine()->getRepository(Category::class)->updatePositions($positions);
    }

    return new JsonResponse(['success' => true, 'data' => $positions]);
  }
}
