<?php

namespace App\Controller\Api;

use App\Entity\Bathhouse;
use App\Repository\BathhouseRepository;
use App\Service\ImageService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Bathhouse controller.
 */
final class BathhouseController extends AbstractFOSRestController
{
    private $serializer;
    /** @var ImageService $imageService */
    private $imageService;

    public function __construct(SerializerInterface $serializer, ImageService $imageService)
    {
      $this->serializer = $serializer;
      $this->imageService = $imageService;
    }

    private function getRepo(): BathhouseRepository
    {   
      return $this->getDoctrine()->getRepository(Bathhouse::class);
    }

    /**
     * Gets a Bathhouse resources
     * @return View
     */
    public function one(int $id): View
    {   
      $bathhouse = $this->getRepo()->findOne($id);
      if (!$bathhouse)
        throw new NotFoundHttpException('Баня не найдена.');
      $serialized = json_decode($this->serializer->serialize($bathhouse, 'json', ['groups' => ['bathhouse:item']]));
      $this->imageService->buildOneImageWithCache($serialized, 'thumb_400_400_png');
      $this->imageService->buildManyImageWithCache($serialized->galleries);
      $this->imageService->buildManyImageWithCache($serialized->additionalServices);
      $images = array_map(fn($element) => $element->image, $serialized->galleries);
      $serialized->galleries = $images;
      return View::create($serialized, Response::HTTP_OK);
    }
}