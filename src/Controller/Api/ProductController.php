<?php

namespace App\Controller\Api;

use App\Entity\House;
use App\Entity\Product;
use App\Repository\HouseRepository;
use App\Repository\ProductRepository;
use App\Service\ImageService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Product controller.
 */
final class ProductController extends AbstractFOSRestController
{
    private $serializer;
    /** @var ImageService $imageService */
    private $imageService;

    public function __construct(SerializerInterface $serializer, ImageService $imageService)
    {
      $this->serializer = $serializer;
      $this->imageService = $imageService;
    }

    private function getRepo(): ProductRepository
    {   
        return $this->getDoctrine()->getRepository(Product::class);
    }

    /**
     * Gets a Products resources
     * @Rest\Get("/products/")
     * @return View
     */
    public function index(Request $request): View
    {   
        $products = [];
        $ids = $request->query->get('id');
        if ($ids) {
          $products = $this->getRepo()->findBy(['id'=> $ids]);
        } else {
          $products = $this->getRepo()->getActive();
        }

        $serialized = json_decode($this->serializer->serialize($products, 'json', ['groups' => ['product:list']]));

        $this->imageService->buildManyImageWithCache($serialized);
        return View::create($serialized, Response::HTTP_OK);
    }
}