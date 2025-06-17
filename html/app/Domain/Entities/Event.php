<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class Event extends Entity
{
    // Константы типов событий (можно расширять при необходимости)
    public const INFO     = 0;
    public const MESSAGE  = 1;
    public const WARNING  = 2;
    public const ERROR    = 3;
    public const FATAL    = 4;

    /** @var int|null Идентификатор */
    public ?int $id = null;

    /** @var int Тип события (использовать константы) */
    public int $type;

    /** @var string Источник события */
    public string $source;

    /** @var string Текст сообщения */
    public string $message;

    /** @var int Время (timestamp UNIX) */
    public int $timestamp;

    /** @var array Дополнительные данные */
    public array $data = [];

    /**
     * Конструктор с автозаполнением timestamp
     */
    public function __construct(array $data = [])
    {
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = time();
        }
        parent::__construct($data);
    }

    /**
     * Удобная фабрика создания
     */
    public static function create(
        int $type,
        string $source,
        string $message,
        ?int $timestamp = null,
        array $data = []
    ): static {
        return new static([
            'type' => $type,
            'source' => $source,
            'message' => $message,
            'timestamp' => $timestamp ?? time(),
            'data' => $data,
        ]);
    }
}
