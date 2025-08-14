<?php
namespace LibrarySystem\Notifications;

interface NotificationInterface {
    public function send(string $message): void;
}
