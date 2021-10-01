<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\House;
use App\Form\Type\HouseType;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HouseController extends AbstractController
{
  /**  */
  private $uploadableManager;

  public function __construct(UploadableManager $uploadableManager)
  {
    $this->uploadableManager = $uploadableManager;
  }

  public function index(Request $request): Response
  {
    $houses = $this->getDoctrine()->getRepository(House::class)->findBy([],['type'=>'ASC', 'position'=>'ASC']);

    return $this->render('admin/house/index.html.twig', [
        'houses' => $houses,
    ]);
  }

  public function edit(Request $request, ?int $id): Response
  {

    if ($id) {
      $house = $this->getDoctrine()->getRepository(House::class)->find($id);
      if (!$house) {
        $this->addFlash('error', 'Произошла ошибка при редактировании товара, попробуйте еще раз');
        $this->redirectToRoute('admin_houses');
      }
    } else {
      $house = new House();
      $house->setEnabled(true);
      $countHouses = count($this->getDoctrine()->getRepository(House::class)->findAll());
      $house->setPosition($countHouses + 1);
    }


    $form = $this->createForm(HouseType::class, $house);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      if (!$house->getId()) {
        $em->persist($house);
      }

      $em->flush();

      return $this->redirect($this->generateUrl('admin_houses'));
    }

    return $this->render('admin\house\edit.html.twig', [
        'form' => $form->createView()
    ]);
  }

  public function delete($id)
  {
    $house = $this->getDoctrine()->getRepository(House::class)->find($id);
    $em = $this->getDoctrine()->getManager();

    $em->remove($house);
    $em->flush();

    return $this->redirectToRoute('admin_houses');
  }
}
