<?php

namespace App\Controller\Admin;

use App\Entity\BathReservation;
use App\Entity\Category;
use App\Entity\House;
use App\Entity\Order;
use App\Entity\Payment;
use App\Form\Type\HouseType;
use App\Repository\BathReservationRepository;
use App\Service\VoucherService;
use DateTimeImmutable;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BathReservationController extends AbstractController
{

  /** @var VoucherService */
  protected $voucherService;

  public function __construct(VoucherService $voucherService)
  {
      $this->voucherService = $voucherService;
  }

  private function getRepo(): BathReservationRepository
  {
    return $this->getDoctrine()->getRepository(BathReservation::class);
  }

  public function index():  Response
  {
    $reservations = $this->getRepo()->findBy([], ['id' => 'DESC']);

    return $this->render('admin\bathreservation\index.html.twig', [
      'reservations' => $reservations
  ]);
  }

  public function show(?int $id): Response
  {
    if (!$id) {
      $this->addFlash('error', 'Не указан id.');
      return $this->redirectToRoute('admin_bathschedules');
    }

    $reservation = $this->getRepo()->find($id);
    if (!$reservation) {
      $this->addFlash('error', 'Не найдено бронирование с таким id.');
      return $this->redirectToRoute('admin_bathschedules');
    }

    $stop_date = $reservation->getDate()->format('Y-m-d');
    $stop_date = new DateTimeImmutable($stop_date);
    $stop_date->modify('+1 day');
    $interval = intval(date_diff(new DateTimeImmutable(), $stop_date)->format('%R%a'));
    $canRefund = true;

    if ($interval < 0) $canRefund = false;

    $statuses = Order::STATUSES;
    $paymentStatuses = Payment::STATUSES;
    return $this->render('admin\bathreservation\show.html.twig', [
        'reservation' => $reservation,
        'statuses' => $statuses,
        'paymentStatuses' => $paymentStatuses,
        'canRefund' => $canRefund
    ]);
  }

  public function sendVoucher(?int $id): Response
  {
    if (!$id) {
      return new JsonResponse(['error' => ['messsage' => 'Не указан id бронирования.']], 400);
    }

    $reservation = $this->getRepo()->find($id);
    if (!$reservation) {
      return new JsonResponse(['error' => ['messsage' => 'Не найдено бронирование с таким id.']], 404);
    }

    $email = $reservation->getCustomer()->getEmail();

    if (!$email) {
      return new JsonResponse(['error' => ['messsage' => 'У клиента не указан email.']], 404);
    }

    $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/vouchers';
    $fileName = base64_encode($reservation->getHash()).".pdf";
    $filePath = $publicDirectory . '/' . $fileName;

    $this->voucherService->createReservationVoucherIfNotExist($reservation, $filePath, $this->getParameter('kernel.project_dir'));
    $this->voucherService->sendReservationVoucher($reservation, $filePath);

    return new JsonResponse([ 'success' => true, 'email' => $email ]);
  }

  public function generateVoucher(?int $id): Response
  {
    if (!$id) {
      return new JsonResponse(['error' => ['messsage' => 'Не указан id бронирования.']], 400);
    }

    $reservation = $this->getRepo()->find($id);
    if (!$reservation) {
      return new JsonResponse(['error' => ['messsage' => 'Не найдено бронирование с таким id.']], 404);
    }

    $email = $reservation->getCustomer()->getEmail();

    if (!$email) {
      return new JsonResponse(['error' => ['messsage' => 'У клиента не указан email.']], 404);
    }

    $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/vouchers';
    $fileName = base64_encode($reservation->getHash()).".pdf";
    $filePath = $publicDirectory . '/' . $fileName;

    $this->voucherService->createReservationVoucher($reservation, $filePath, $this->getParameter('kernel.project_dir'));

    return new JsonResponse([ 'success' => true, 'url' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL). 'vouchers/' . $fileName ]);
  }
}
