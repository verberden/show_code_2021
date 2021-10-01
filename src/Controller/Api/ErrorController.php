<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Error;
use App\Repository\CategoryRepository;
use App\Service\FrontErrorLoggingService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Error controller.
 */
final class ErrorController extends AbstractFOSRestController
{
    private $serializer;
    /** @var FrontErrorLoggingService $frontErrorLoggingService */
    private $frontErrorLoggingService;

    public function __construct(SerializerInterface $serializer, FrontErrorLoggingService $frontErrorLoggingService)
    {
      $this->serializer = $serializer;
      $this->frontErrorLoggingService = $frontErrorLoggingService;
    }

    /**
     * Create error
     * @return View
     */
    public function create(Request $request): View
    { 
      $incomingData = json_decode($request->getContent(), true);  
      $errorData = $incomingData['error'];
      
      if (!$errorData) {
        return View::create('Не указана ошибка.', Response::HTTP_BAD_REQUEST);
      }
      $route = $incomingData['route'] ?? NULL;
      $data = $incomingData['data'] ?? NULL;
      $description = $incomingData['description'] ?? NULL;

      $this->frontErrorLoggingService->logError($errorData, $route, $data, $description);
      return View::create(null, Response::HTTP_OK);
    }
}