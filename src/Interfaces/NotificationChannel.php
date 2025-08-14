<?php
namespace LibrarySystem\Interfaces;

interface NotificationChannel
{
    public function send(string $to, string $subject, string $body): bool;
}