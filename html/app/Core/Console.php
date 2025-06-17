<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class Console
{
    protected static array $messages = [];
    const MAX_CONSOLE_MESSAGES = 100;

    /**
     * Добавить сообщение в консоль (сессию)
     */
    public static function setMessage(array $msg): void
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        if (count(self::$messages) >= self::MAX_CONSOLE_MESSAGES) {
            array_shift(self::$messages);
        }
        self::$messages[] = $msg;
        $_SESSION['console_messages'] = self::$messages;
    }

    /**
     * Получить сообщения, опционально с фильтрацией по level
     */
    public static function getMessages(int $filterLevel = 0): array
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        if ($filterLevel === null || $filterLevel === 0) {
            return self::$messages;
        }
        return array_values(array_filter(self::$messages, function ($msg) use ($filterLevel) {
            return isset($msg['type']) && (int)$msg['type'] >= $filterLevel;
        }));
    }

    /**
     * Получить количество сообщений в консоли
     */
    public static function count(): int
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        return count(self::$messages);
    }

    /**
     * Получить последнее сообщение (или null)
     */
    public static function getLastMessage(): ?array
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        return !empty(self::$messages) ? end(self::$messages) : null;
    }

    /**
     * Очистить все сообщения консоли
     */
    public static function clearMessages(): void
    {
        self::$messages = [];
        unset($_SESSION['console_messages']);
    }
}
