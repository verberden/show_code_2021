<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use Symfony\Bridge\Monolog\Logger;

class TelegramService {

  /** @var EntityManagerInterface $em */
  private $em;

  /** @var UserRepository $userRepository */
  private $userRepository;

  /** @var BotApi $api */
  private $api;

  /** @var \Symfony\Bridge\Monolog\Logger $logger */
  private $logger;

  public function __construct(EntityManagerInterface $em, UserRepository $userRepository, BotApi $api, LoggerInterface $telegramLogger)
  {
    $this->userRepository = $userRepository;
    $this->em = $em;
    $this->api = $api;
    $this->logger = $telegramLogger;
  }

  public function registerCustomerTelegram(string $telegramHash, string $chatId, string $telegramUserId): bool
  {
      $user = $this->userRepository->findOneBy(['telegramHash' => $telegramHash]);

      if(!$user) return false;

      $user->setTelegramChatId($chatId);
      $user->setTelegramUserId($telegramUserId);
      $this->em->persist($user);
      $this->em->flush();

      return true;
  }

  public function sendMessage(?string $chatId, ?string $message)
  {
    try {
      if ($chatId && $message) {
        $this->api->sendMessage($chatId, $message);
      }
    } catch (\Exception $exception) {
      $this->logger->error("Ошибка отправки сообщения в чат c id " . $chatId . ' с текстом: '. $message .'. Exception_error=' . $exception->getMessage());
    }
  }

  public function sendToAllAdmins(string $message, $paymentType = 'order_payment')
  {
    /** @var User[] $users */
    $users = $this->userRepository->findAdminsWithTelegramChat($paymentType);

    foreach ($users as $user) {
      $this->sendMessage($user->getTelegramChatId(), $message);
    }
  }
}