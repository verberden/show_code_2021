<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;

class UserService {

    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function findDeliveryMan(int $id)
    {

        return $this->userRepository->findDeliveryMan($id);

    }

    public function findAllDeliveryMen()
    {

        return $this->userRepository->findAllDeliveryMen();

    }
}