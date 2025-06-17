<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\EventLoggerInterface;
use GIG\Domain\Entities\Entity;

class FileEventLogger implements EventLoggerInterface
{
    protected string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public function log(Entity $event): void
    {
        file_put_contents($this->logFile, json_encode($event, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

        public function logLine(string $str): void
    {
        file_put_contents($this->logFile, $str . PHP_EOL, FILE_APPEND);
    }
}