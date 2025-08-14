<?php
namespace LibrarySystem\Notifications;

use LibrarySystem\Interfaces\NotificationChannel;
use LibrarySystem\Traits\LoggerTrait;

class EmailNotification implements NotificationChannel
{
    use LoggerTrait;

    public function send(string $to, string $subject, string $body): bool
    {
        // Simulate email send
        $this->log("Email to $to | $subject | " . substr($body,0,60));
        return true;
    }
}