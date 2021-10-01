<?php

namespace App\Telegram;

use App\Service\TelegramService;
use BoShurik\TelegramBotBundle\Telegram\Command\CommandInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

class RegisterCommand implements CommandInterface
{

  /**
   * RegExp for command
   */
  public const REGEXP = '/^([^\s@]+)(@\S+)?\s?(.*)$/';

  private $description;

  /** @var TelegramService $telegramService */
  private $telegramService;

  /** @var \Symfony\Bridge\Monolog\Logger $logger */
  private $logger;

  public function __construct($description = 'Команда регистрации', TelegramService $telegramService, LoggerInterface $telegramLogger)
  {
    $this->description = $description;
    $this->telegramService = $telegramService;
    $this->logger = $telegramLogger;
  }

  /**
   * @inheritDoc
   */
  public function execute(BotApi $api, Update $update)
  {
    $message = trim(str_replace($this->getName(), '', $update->getMessage()->getText()));
    $chatId = $update->getMessage()->getChat()->getId();
    $userId = $update->getMessage()->getFrom()->getId();

    try {
      $result = $this->telegramService->registerCustomerTelegram($message, $chatId, $userId);
    } catch (\Exception $exception) {
      $this->logger->error("Ошибка выполнения команды " . $this->getName() . '. Exception_error=' . $exception->getMessage());
      $api->sendMessage($chatId, 'Не удалось зарегистрироваться в чат боте. Попробуйте позже или обратитесь в службу поддержки.');
      return;
    }

    $reply = 'Вы успешно зареистрированы в чат-боте.';
  
    if ($result === false) {
      $reply = 'Указан неверный токен.';
    }

    $api->sendMessage($chatId, $reply);

  }

  /**
   * @inheritDoc
   */
  public function getName()
  {
      return '/register';
  }

  /**
   * @inheritDoc
   */
  public function getDescription()
  {
      return $this->description;
  }

  public function isApplicable(Update $update)
  {
      $message = $update->getMessage();

      if (null === $message || !\strlen($message->getText())) {
          return false;
      }

      if ($this->matchCommandName($message->getText(), $this->getName())) {
          return true;
      }

      return false;
  }

  /**
   * @param string $text
   * @param string $name
   *
   * @return bool
   */
  protected function matchCommandName($text, $name)
  {
      preg_match(self::REGEXP, $text, $matches);

      return !empty($matches) && $matches[1] == $name;
  }
}
