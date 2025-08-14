<?php
namespace LibrarySystem\Notifications;

class EmailNotification implements NotificationInterface {
    public function send(string $message): void {
        echo "<p>📧 Email sent: {$message}</p>";
    }
}
