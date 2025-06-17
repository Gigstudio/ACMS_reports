<?php
namespace GIG\Domain\Services;

use GIG\Domain\Entities\Event;
use GIG\Infrastructure\Contracts\EventLoggerInterface;
use GIG\Core\Console;

defined('_RUNKEY') or die;

/**
 * Менеджер событий.
 * Управляет логированием, доступом к описаниям типов событий, маршрутизацией через логгеры.
 */
class EventManager
{
    /** @var EventLoggerInterface|null */
    protected static ?EventLoggerInterface $logger = null;

    /** @var array Кэш конфигурации событий (type => ['title' => ..., 'class' => ...]) */
    protected static array $config = [];

    /**
     * Устанавливает логгер событий (например, FileEventLogger)
     */
    public static function setLogger(EventLoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Регистрирует событие (экземпляр Event)
     */
    public static function log(Event $event): void
    {
        if (self::$logger) {
            self::$logger->log($event);
        }
        if ($event->type >= Event::WARNING) {
            Console::setMessage($event->toArray(['type','source','message','timestamp','data']));
        }
    }

    /**
     * Быстрый "сахар" для совместимости с прежними вызовами
     */
    public static function logParams(
        int $type,
        string $source,
        string $message,
        array $data = [],
        ?int $timestamp = null
    ): void {
        $event = Event::create($type, $source, $message, $timestamp, $data);
        self::log($event);
    }

    /**
     * Получить "человеческое" название события по типу
     */
    public static function getTitle(int $type): string
    {
        self::loadConfig();
        return self::$config[$type]['title'] ?? '';
    }

    /**
     * Получить CSS-класс или "семантику" события по типу
     */
    public static function getClass(int $type): string
    {
        self::loadConfig();
        return self::$config[$type]['class'] ?? '';
    }

    /**
     * Подгрузка и кэширование конфигурации событий
     */
    protected static function loadConfig(): void
    {
        if (!self::$config) {
            self::$config = include(PATH_CONFIG . 'events.php');
        }
    }

    /**
     * Для тестов — сброс логгера и конфига
     */
    public static function reset(): void
    {
        self::$logger = null;
        self::$config = [];
    }
}
