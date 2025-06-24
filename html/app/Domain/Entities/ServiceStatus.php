<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class ServiceStatus extends Entity
{
    public int $id;
    public string $service;
    public string $status;
    public string $message = '';
    public $details = null; // array|null
    public string $created_at = '';
    public string $updated_at  = '';

    public function __construct(array $data = [])
    {
        // details — автодекод из json, если строка
        if (isset($data['details']) && is_string($data['details'])) {
            $decoded = json_decode($data['details'], true);
            $data['details'] = is_array($decoded) ? $decoded : $data['details'];
        }
        parent::__construct($data);
    }
}
