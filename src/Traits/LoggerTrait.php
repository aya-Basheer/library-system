<?php
namespace LibrarySystem\Traits;

trait LoggerTrait {
    public function log(string $message): void {
        echo "<p>📝 Log: {$message}</p>";
    }
}
