<?php

namespace App\Controller\Api;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Setting controller.
 */
final class SettingController extends AbstractFOSRestController
{
  private $serializer;

  public function __construct(SerializerInterface $serializer)
  {
    $this->serializer = $serializer;
  }

  private function getRepo(): SettingRepository
  {
    return $this->getDoctrine()->getRepository(Setting::class);
  }

  /**
   * Gets a Settings resources
   * @Rest\Get("/settings")
   * @return View
   */
  public function index(): View
  {

    $settings = $this->getRepo()->findBy(['hide' => false], ['name' => 'ASC']);

    $serialized = json_decode($this->serializer->serialize($settings, 'json', ['groups' => ['setting:list']]));
    return View::create($serialized, Response::HTTP_OK);
  }

  /**
   * Gets a Products resources
   * @Rest\Get("/setting/{name}")
   * @return View
   */
  public function one(string $name): View
  {

    $setting = $this->getRepo()->findOneBy(['hide' => false, 'name' => $name]);

    if (!$setting) {
      return View::create(null, Response::HTTP_NOT_FOUND);
    }

    $serialized = json_decode($this->serializer->serialize($setting, 'json', ['groups' => ['setting:item']]));
    return View::create($serialized, Response::HTTP_OK);
  }


  public function versionControl()
  {
    $versionGoogle = $this->container->get('parameter_bag')->get('version_google');
    $versionApple = $this->container->get('parameter_bag')->get('version_apple');
    $googleMerchantId = base64_encode($this->container->get('parameter_bag')->get('sberbank_merchant'));

    $serialized = json_decode($this->serializer->serialize([
        'versionGoogle' => $versionGoogle,
        'versionApple' => $versionApple,
        'googleMerchantId' => $googleMerchantId,
    ], 'json'));
    return View::create($serialized, Response::HTTP_OK);
  }
}