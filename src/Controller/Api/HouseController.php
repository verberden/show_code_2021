<?php

namespace App\Controller\Api;

use App\Entity\House;
use App\Repository\HouseRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * House controller.
 */
final class HouseController extends AbstractFOSRestController
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
      $this->serializer = $serializer;
    }

    private function getRepo(): HouseRepository
    {   
        return $this->getDoctrine()->getRepository(House::class);
    }

    /**
     * Gets a Houses resources
     * @Rest\Get("/houses/")
     * @return View
     */
    public function index(): View
    {   
        $houses = $this->getRepo()->findAllEnabled();
        $serialized = json_decode($this->serializer->serialize($houses, 'json', ['groups' => ['house:list']]));
        return View::create($serialized, Response::HTTP_OK);
    }
}