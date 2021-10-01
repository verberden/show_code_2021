<?php

namespace App\Service;

use App\Entity\Error;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;

class FrontErrorLoggingService {

    /** @var EntityManagerInterface $em */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function saveToDb($errorData, $route, $data, $description) {
      $error = new Error();
      $error->setError(is_string($errorData) ? ["error" => $errorData] : $errorData);
      $error->setRoute($route);
      $error->setData($data);
      $error->setDescription($description);

      $this->em->persist($error);

      $this->em->flush();
    }

    public function logError($errorData, $route = NULL, $data = NULL, $description = NULL)
    {
      // Здесь решить как выводить ошибку. Можно и в лог писать.
      $this->saveToDb($errorData, $route, $data, $description);
    }
}