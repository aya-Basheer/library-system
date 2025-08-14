<?php
namespace LibrarySystem\Notifications;

use LibrarySystem\Interfaces\NotificationChannel;
use LibrarySystem\Traits\LoggerTrait;

class SmsNotification implements NotificationChannel
{
    use LoggerTrait;

    public function send(string $to, string $subject, string $body): bool
    {
        // Simulate SMS send (subject ignored)
        $this->log("SMS to $to | " . substr($body,0,60));
        return true;
    }
}