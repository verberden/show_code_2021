<?php

namespace App\Service;

use App\Entity\BathReservation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Swift_Attachment;
use Swift_Mailer;
use Twig\Environment as TwigEngine;

class MailService 
{

  /** @var Swift_Mailer */
  protected $mailer;

  /** @var VoucherService */
  protected $voucherService;

  public function __construct(Swift_Mailer $mailer)
  {
      $this->mailer = $mailer;
  }

  public function send(string $subject, string $sendFrom, array $sendTo, string $body, ?array $filePaths)
  {
    $message = (new \Swift_Message())
      ->setSubject($subject)
      ->setFrom([$sendFrom])
      ->setTo($sendTo)
      // ->setBcc('dimawar@mail.ru')
      ->setBody($body, 'text/html');

    if ($filePaths && count($filePaths)) {
      foreach ($filePaths as $filePath) {
        $message->attach(Swift_Attachment::fromPath($filePath));
      }
    }
     
    $this->mailer->send($message);
  }
}