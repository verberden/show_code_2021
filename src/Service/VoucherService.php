<?php

namespace App\Service;

use App\Entity\BathReservation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Twig\Environment as TwigEngine;

class VoucherService 
{

  /** @var TwigEngine */
  protected $twig;

  /** @var MailService */
  protected $mailer;


  public function __construct(TwigEngine $twig, MailService $mailer)
  {
    $this->twig = $twig;
    $this->mailer = $mailer;
  }

  public function createReservationVoucher(BathReservation $reservation, string $flePath, string $projectDir)
  {
    // Configure Dompdf according to your needs
    $pdfOptions = new Options();
    $pdfOptions->set('defaultFont', 'DejaVu Sans');
    // Instantiate Dompdf with our options
    $dompdf = new Dompdf($pdfOptions);

    $dompdf->getOptions()->setChroot($projectDir . DIRECTORY_SEPARATOR . 'public');  //path to document root
    // Retrieve the HTML generated in our twig file

    /** @var BathSlot[] $slots */
    $slots = $reservation->getSlots();
    $countSlots = count($slots);
    $startTime = $slots[0]->getBathschedule()->getDate()->format('Y-m-d'). " " .explode('-', $slots[0]->getTime())[0];
    $endTime = $slots[$countSlots - 1]->getBathschedule()->getDate()->format('Y-m-d'). " " .explode('-', $slots[$countSlots - 1]->getTime())[1];
    $html = $this->twig->render('pdf/voucher.html.twig', [
      'title' => "Ваучер бронирования",
      'assetDir' => $projectDir . DIRECTORY_SEPARATOR . 'public',
      'reservation' => $reservation,
      'startTime' => $startTime,
      'endTime' => $endTime
    ]);

    $dompdf->loadHtml($html);
    
    // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Store PDF Binary Data
    $output = $dompdf->output();
    
    // Write file to the desired path
    file_put_contents($flePath, $output);      
  }

  public function createReservationVoucherIfNotExist(BathReservation $reservation, string $flePath, string $projectDir)
  {
    if (!file_exists($flePath)) {
      $this->createReservationVoucher($reservation, $flePath, $projectDir);
    }
  }

  public function sendReservationVoucher(BathReservation $reservation, string $voucherFilePath)
  {
    $email = $reservation->getCustomer()->getEmail();

    $body = $this->twig->render(
      'Email/voucher.html.twig',
      ['reservation' => $reservation],
    );

    $this->mailer->send('Ваучер бронирования', 'test_bele@mail.ru', [$email], $body, [$voucherFilePath]);
  }
}