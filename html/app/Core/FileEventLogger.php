<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;

class FileEventLogger
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

    public function log(Event $event): void
    {
        // $line = json_encode($event->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $line = EventManager::formatForLog($event);
        file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND);
    }

    public function load(?int $typeFilter = null): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $events = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!is_array($data)) {
                continue;
            }

            if ($typeFilter === null || (int)($data['type'] ?? -1) === $typeFilter) {
                $events[] = new Event($data);
            }
        }

        return $events;
    }
}