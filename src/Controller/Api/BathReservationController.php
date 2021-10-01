<?php

namespace App\Controller\Api;

use App\Entity\BathReservation;
use App\Repository\BathReservationRepository;
use App\Service\ImageService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * BathReservation controller.
 */
final class BathReservationController extends AbstractFOSRestController
{
  private $serializer;
  /** @var ImageService $imageService */
  private $imageService;

  public function __construct(SerializerInterface $serializer, ImageService $imageService)
  {
    $this->serializer = $serializer;
    $this->imageService = $imageService;
  }

  private function getRepo(): BathReservationRepository
  {   
      return $this->getDoctrine()->getRepository(BathReservation::class);
  }

  /**
   * Gets a Bath Reservation resource
   * @return View
   */
  public function one(string $hash): View
  {   
    $bathReservation = $this->getRepo()->findOneBy(['hash' => $hash]);
    if (!$bathReservation)
      throw new NotFoundHttpException('Бронирование бани не найдено.');
    
    $statusText = $bathReservation->getStatusText();
    $serialized = json_decode($this->serializer->serialize($bathReservation, 'json', ['groups' => ['reservation:item']]));
    $serialized->statusText = $statusText;
    $serialized->date = $bathReservation->getSlots()[0]->getBathschedule()->getDate();
    $serialized->bathouseId = $bathReservation->getSlots()[0]->getBathschedule()->getBathhouse()->getId();

    $serialized->additionalServiceItems = array_map(function ($el) {
      if (isset($el->additionalService) && $el->additionalService) {
        $el->image = $el->additionalService->image;
        unset($el->additionalService);
      }
      return $el;
    }, $serialized->additionalServiceItems);

    $this->imageService->buildManyImageWithCache($serialized->additionalServiceItems);

    return View::create($serialized, Response::HTTP_OK);
  }
}