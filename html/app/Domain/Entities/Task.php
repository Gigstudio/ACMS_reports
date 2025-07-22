<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class Task extends Entity
{
    // public const PENDING = 'PENDING';
    // public const RUNNING = 1;
    // public const DONE    = 2;
    // public const ERROR   = 3;
    // public const ABORTED = 4;

    public ?int $id = null;
    public string $type = '';
    public string $status = 'PENDING';
    public array $params = [];
    public float $progress = 0.0;
    public array $result = [];
    public array $log = [];
    public ?int $user_id = null;
    public string $created_at = '';
    public ?string $updated_at = null;
    public ?string $finished_at = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) continue;
            if (in_array($key, ['params', 'log', 'result'])) {
                $this->$key = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
            } else {
                $this->$key = $value;
            }
        }
    }

    public function toArray(array $only = []): array
    {
        // Кодируем json-поля обратно
        $data = parent::toArray($only);
        foreach (['params', 'log', 'result'] as $jsonField) {
            $data[$jsonField] = !empty($this->$jsonField)
                ? json_encode($this->$jsonField, JSON_UNESCAPED_UNICODE)
                : null;
        }
        return $data;
    }
}