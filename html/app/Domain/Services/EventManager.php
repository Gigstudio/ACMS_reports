<?php
namespace GIG\Domain\Services;

use GIG\Domain\Entities\Event;
use GIG\Infrastructure\Repository\EventRepository;
use GIG\Core\Console;
use GIG\Core\Config;
use GIG\Core\FileEventLogger;

defined('_RUNKEY') or die;

class EventManager
{
    /** @var ?EventRepository */
    protected static $repository = null;

    /** @var ?FileEventLogger */
    protected static $logger = null;
    protected static array $eventsMap = [];

    protected static function getEventsMap(): array
    {
        if (!self::$eventsMap) {
            self::$eventsMap = include PATH_CONFIG . 'events.php';
        }
        return self::$eventsMap;
    }

    /**
     * Задать файловый логгер (или иной)
     */
    public static function setLogger($logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Задать EventRepository (когда готова БД)
     */
    public static function setRepository(EventRepository $repository): void
    {
        self::$repository = $repository;
    }

    /**
     * Основной метод логирования Event
     */
    public static function log(Event $event): void
    {
        // 1. В БД (если возможно)
        if (self::$repository) {
            self::$repository->save($event);
        }
        // 2. В лог-файл (всегда, если задан)
        if (self::$logger) {
            $str = self::formatForLog($event);
            self::$logger->logLine($str);
        }
        // 3. В консоль (если есть сессия)
        if (class_exists(Console::class)) {
            Console::setMessage([
                'type'      => $event->type,
                'time'      => date('Y-m-d H:i:s', $event->timestamp),
                'class'     => self::getClass($event->type),
                'source'    => self::extractSource($event->source),
                'message'   => $event->message,
            ]);
        }
    }

    /**
     * Сахар: создать и залогировать событие в одну строку
     */
    public static function logParams(int $type, string $source, string $message, array $data = []): void
    {
        $event = new Event([
            'type'      => $type,
            'source'    => $source,
            'message'   => $message,
            'timestamp' => time(),
            'data'      => $data,
        ]);
        self::log($event);
    }

    /**
     * Форматирует строку для человекочитаемого лог-файла.
     */
    public static function formatForLog(Event $event): string
    {
        $class = self::getClass($event->type);
        $time  = date('Y-m-d H:i:s', $event->timestamp);
        $src   = $event->source;
        $msg   = $event->message;
        return sprintf("[%s][%s][%s] %s", $time, strtoupper($class), $src, $msg);
    }

    /**
     * Вернуть css-класс события по типу (из events.php)
     */
    public static function getClass(int $type): string
    {
        $events = self::getEventsMap();
        return $events[$type]['class'] ?? 'info';
    }

    /**
     * Вернуть "человеческое" название типа события (из events.php)
     */
    public static function getTitle(int $type): string
    {
        $events = self::getEventsMap();
        return $events[$type]['title'] ?? 'Информация';
    }

    public static function extractSource($source): string
    {
        if (is_string($source) && str_contains($source, '/')) {
            return basename($source, '.php');
        }
        if (is_string($source) && str_contains($source, '\\')) {
            $parts = explode('\\', $source);
            return end($parts);
        }
        return (string)$source;
    }
}
