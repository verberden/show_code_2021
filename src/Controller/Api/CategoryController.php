<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\ImageService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Category controller.
 */
final class CategoryController extends AbstractFOSRestController
{
    private $serializer;
    /** @var ImageService $imageService */
    private $imageService;

    public function __construct(SerializerInterface $serializer, ImageService $imageService)
    {
      $this->serializer = $serializer;
      $this->imageService = $imageService;
    }

    private function getRepo(): CategoryRepository
    {   
        return $this->getDoctrine()->getRepository(Category::class);
    }

    /**
     * Gets a Categories resources
     * @return View
     */
    public function index(): View
    {   
        $categories = $this->getRepo()->findAllEnabled();
        $serialized = json_decode($this->serializer->serialize($categories, 'json', ['groups' => ['category:list']]));
        $this->imageService->buildManyImageWithCache($serialized, 'thumb_400_400_png');
        return View::create($serialized, Response::HTTP_OK);
    }

    /**
     * Gets a Categories resources
     * @return View
     */
    public function one(int $id): View
    {   
        $category = $this->getRepo()->findOne($id);
        if (!$category)
          throw new NotFoundHttpException('Категория не найдена.');
        $serialized = json_decode($this->serializer->serialize($category, 'json', ['groups' => ['category:item']]));
        $this->imageService->buildOneImageWithCache($serialized, 'thumb_400_400_png');
        $this->imageService->buildManyImageWithCache($serialized->products);
        return View::create($serialized, Response::HTTP_OK);
    }
}