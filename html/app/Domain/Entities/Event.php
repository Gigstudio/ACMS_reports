<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

use GIG\Core\Application;

class Event extends Entity
{
    public const INFO     = 0;
    public const MESSAGE  = 1;
    public const WARNING  = 2;
    public const ERROR    = 3;
    public const FATAL    = 4;

    public ?int $id = null;
    public int $type;
    public string $source;
    public string $message;
    public int $timestamp;
    public string $ip;
    public ?int $userId = null;

    /** Дополнительные данные (сериализуемые) */
    public array $data = [];

    public function __construct(array $properties = [])
    {
        $properties['timestamp'] ??= time();
        $properties['ip'] ??= Application::getInstance()->request->getIp();
        parent::__construct($properties);
    }

    public static function create(
        int $type,
        string $source,
        string $message,
        array $data = [],
        ?int $userId = null
    ): static {
        return new static([
            'type' => $type,
            'source' => $source,
            'message' => $message,
            'data' => $data,
            'userId' => $userId,
        ]);
    }}
