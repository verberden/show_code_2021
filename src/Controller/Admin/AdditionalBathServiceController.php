<?php
namespace App\Controller\Admin;

use App\Entity\AdditionalBathService;
use App\Form\Type\AdditionalBathServiceType;
use App\Repository\AdditionalBathServiceRepository;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AdditionalBathServiceController extends AbstractController
{

  /** @var UploadableManager $uploadableManager  */
  private $uploadableManager;

  public function __construct(UploadableManager $uploadableManager)
  {
    $this->uploadableManager = $uploadableManager;
  }

  private function getRepo(): AdditionalBathServiceRepository
  {
    return $this->getDoctrine()->getRepository(AdditionalBathService::class);
  }

  public function index()
  {
    $services = $this->getRepo()->findAll();

    return $this->render('admin\additionalbathservice\index.html.twig', [
      'services' => $services
    ]);
  }

  public function create(Request $request)
  {
    $service = new AdditionalBathService();

    $form = $this->createForm(AdditionalBathServiceType::class, $service);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      if ($service->getImage() && $service->getImage()->getFile()) {
        $service->getImage()->setUploadPath('media/bathservice_image');
        $this->uploadableManager->markEntityToUpload($service->getImage(), $service->getImage()->getFile());
      }

      $service->setPrice($service->getPrice() * 100);
      if (!$service->getId()) {
        $em->persist($service);
      }

      $em->flush();

      return $this->redirect($this->generateUrl('admin_bathservices'));
    }

    return $this->render('admin\additionalbathservice\edit.html.twig', [
        'form' => $form->createView()
    ]);
  }

  public function update(Request $request, $id)
  {
    $service = $this->getRepo()->findOneBy(['id' => $id]);
    $service->setPrice($service->getPrice() / 100);

    $form = $this->createForm(AdditionalBathServiceType::class, $service);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      if ($service->getImage() && $service->getImage()->getFile()) {
        $service->getImage()->setUploadPath('media/bathservice_image');
        $this->uploadableManager->markEntityToUpload($service->getImage(), $service->getImage()->getFile());
      }

      $service->setPrice($service->getPrice() * 100);

      $em->flush();

      return $this->redirect($this->generateUrl('admin_bathservices'));
    }

    return $this->render('admin\additionalbathservice\edit.html.twig', [
      'form' => $form->createView()
    ]);
  }
}