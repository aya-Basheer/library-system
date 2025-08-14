<?php
namespace LibrarySystem\Traits;

trait LoggerTrait
{
    private string $logFile = __DIR__ . '/../../logs/app.log';

    protected function log(string $message): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $time = date('Y-m-d H:i:s');
        $line = "[$time] " . static::class . ": $message" . PHP_EOL;
        @file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}