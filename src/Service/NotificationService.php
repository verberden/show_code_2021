<?php

namespace App\Service;

use Exception;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\RawMessageFromArray;

class NotificationService {
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function send(?string $deviceToken, array $notificationBody)
    {
      if ($deviceToken) {
        $message = new RawMessageFromArray([
          'android' => [
            'ttl' => '3600s',
            'priority' => 'high',
          ],
          'data' => $notificationBody,
          'apns' => [
              // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#apnsconfig
              'headers' => [
                  'apns-priority' => '10',
              ],
              'payload' => [
                  'aps' => [
                      'contentAvailable' => true,
                      'alert' => $notificationBody,
                      'badge' => 1,
                      'sound' => 'default'
                  ],
              ],
          ],
          'webpush' => [
              // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#webpushconfig
              'headers' => [
                  'Urgency' => 'normal',
              ],
              'notification' => array_merge($notificationBody, ['vibrate' => [200, 100, 200]]),
          ],
        'token' => $deviceToken,
      ]);

        try {
          $this->messaging->send($message);
        } catch (\Exception $exception) {
          throw new Exception($exception);
        }

      }

    }
}