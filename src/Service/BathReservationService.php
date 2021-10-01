<?php

namespace App\Service;

use App\Entity\AdditionalBathService;
use App\Entity\BathReservation;
use App\Entity\BathReservationItems;
use App\Entity\Error;
use App\Repository\AdditionalBathServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BathReservationService {

    /** @var AdditionalBathServiceRepository $additionalServiceRepository */
    private $additionalServiceRepository;

    public function __construct(AdditionalBathServiceRepository $additionalServiceRepository)
    {
      $this->additionalServiceRepository = $additionalServiceRepository;
    }

    public function createReservationItems(?array $additionalServices): array
    {
      $results = [];

      if (!$additionalServices)
        return $results;

      $ids = [];
      foreach($additionalServices as $service) {
        if (!isset($service['serviceId'])) {
          throw new NotFoundHttpException('У сервиса не указан id');
        }

        array_push($ids, $service['serviceId']);
      }

      $services = $this->additionalServiceRepository->findBy(['id' => $ids]);
      if (count($services) !== count($additionalServices)) {
        $findIds = array_map(fn($el) => $el->getId(), $services);
        $diffIds = array_diff($ids, $findIds);
        throw new NotFoundHttpException('Не найдены сервисы со следующими id: '.implode(',', $diffIds));
      }

      $results = array_map(function(AdditionalBathService $service) use($additionalServices) {
        $key = array_search($service->getId(), array_column($additionalServices, 'serviceId'));
        $quantity = $additionalServices[$key]['quantity'];

        $newService = new BathReservationItems();
        $newService->setName($service->getName());
        $newService->setPrice($service->getPrice());
        $newService->setQuantity($quantity);
        $newService->setCost(intval($quantity) * $service->getPrice());
        $newService->setAdditionalService($service);
        
        return $newService;
      }, $services);

      return $results;
    }
}