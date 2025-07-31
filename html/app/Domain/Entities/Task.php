<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class Task extends Entity
{
    // public const PENDING = 0;
    // public const RUNNING = 1;
    // public const DONE    = 2;
    // public const ERROR   = 3;
    // public const ABORTED = 4;

    public ?int $id = null;
    public string $type = '';
    public ?string $name = null;
    public string $status = 'PENDING';
    public string $execution_type = 'ONCE';
    public ?int $interval_minutes = null;
    public ?string $last_run_at = null;
    public array $params = [];
    public float $progress = 0.0;
    public array $result = [];
    public array $log = [];
    public ?int $user_id = null;
    public string $created_at;
    public ?string $updated_at = null;
    public ?string $finished_at = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) continue;

            if (in_array($key, ['params', 'log', 'result'], true)) {
                $this->$key = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
            } elseif ($key === 'interval_minutes') {
                $this->$key = is_numeric($value) ? (int)$value : null;
            } elseif ($key === 'user_id') {
                $this->$key = is_numeric($value) ? (int)$value : null;
            } elseif ($key === 'progress') {
                $this->$key = (float)$value;
            } else {
                $this->$key = $value;
            }
        }
    }

    public function isRecurring(): bool
    {
        return $this->execution_type === 'RECURRING';
    }

    public function isDueToRun(): bool
    {
        if (!$this->isRecurring()) return false;
        if ($this->last_run_at === null) return true;

        $last = strtotime($this->last_run_at);
        return time() - $last >= ($this->interval_minutes ?? 0) * 60;
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