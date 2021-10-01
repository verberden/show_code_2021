<?php

namespace App\Controller\Api;

use App\Entity\BathReservation;
use App\Repository\BathReservationRepository;
use App\Service\ImageService;
use App\Service\VoucherService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;


use Dompdf\Dompdf;
use Dompdf\Options;
use Swift_Attachment;
use Swift_Mailer;
use Twig\Environment as TwigEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * VoucherController controller.
 */
final class VoucherController extends AbstractFOSRestController
{
  /** @var TwigEngine */
  protected $twig;

  /** @var Swift_Mailer */
  protected $mailer;

  /** @var VoucherService */
  protected $voucherService;

  public function __construct(TwigEngine $twig, Swift_Mailer $mailer, VoucherService $voucherService)
  {
      $this->twig = $twig;
      $this->mailer = $mailer;
      $this->voucherService = $voucherService;
  }

  private function getRepo(): BathReservationRepository
  {   
      return $this->getDoctrine()->getRepository(BathReservation::class);
  }

  /**
   * Gets a Bath Reservation resource
   */
  public function voucher(string $hash)
  {  

    $reservation = $this->getRepo()->findOneBy(['hash' => $hash]);

    if (!$reservation) {
      return new Response('Нет такого бронирования.', 404);
    }

    $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/vouchers';
    $fileName = base64_encode($hash) . ".pdf";
    $filePath= $publicDirectory . '/' . $fileName;
    $this->voucherService->createReservationVoucherIfNotExist($reservation, $filePath, $this->getParameter('kernel.project_dir'));
     
    return new JsonResponse(['url' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL). 'vouchers/' . $fileName]);
  }

  public function send(string $hash)
  {  

    $reservation = $this->getRepo()->findOneBy(['hash' => $hash]);

    if (!$reservation) {
      return new Response('Нет такого бронирования.', 404);
    }

    $email = $reservation->getCustomer()->getEmail();

    if (!$email) {
      return new Response('У клиента не указан email.', 404);
    }

    $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/vouchers';
    $fileName = base64_encode($hash).".pdf";
    $filePath = $publicDirectory . '/' . $fileName;
    $this->voucherService->createReservationVoucherIfNotExist($reservation, $filePath, $this->getParameter('kernel.project_dir'));
    $this->voucherService->sendReservationVoucher($reservation, $filePath);
     
    return new JsonResponse(['email' => $email ]);
  }
}