<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Domain\Entities\ServiceStatus;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Entities\Event;

class ServiceStatusManager
{
    protected DatabaseClientInterface $db;

    public function __construct(DatabaseClientInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Установить/обновить статус сервиса.
     * @param string $service  — имя сервиса
     * @param string $status   — status: 'ok', 'fail', 'warn', 'busy'
     * @param string|null $message — пояснение
     * @param array|string|null $details — дополнительные данные (json или текст)
     * @return bool
     */
    public function setStatus(string $service, string $status, ?string $message = null, ?string $details = null): bool
    {
        // Валидация статуса
        $allowed = ['ok', 'fail', 'warn', 'busy'];
        if (!in_array($status, $allowed, true)) {
            throw new GeneralException("Недопустимый статус сервиса", 400, [
                'detail' => "Статус должен быть одним из: " . implode(', ', $allowed)
            ]);
        }

        $data = [
            'status'     => $status,
            'message'    => $message ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($details !== null) {
            $data['details'] = json_encode($details, JSON_UNESCAPED_UNICODE);
        } else {
            $data['details'] = null;
        }
        $found = $this->db->first('service_status', ['service' => $service]);
        if ($found) {
            $success = $this->db->update('service_status', $data, ['service' => $service]);
            $msg = "Обновлен статус сервиса $service: $status";
        } else {
            $data['service'] = $service;
            $data['created_at'] = date('Y-m-d H:i:s');
            $success = $this->db->insert('service_status', $data);
            $msg = "Добавлен статус сервиса $service: $status";
        }

        if (!$success) {
            throw new GeneralException("Не удалось обновить статус сервиса", 500, [
                'detail' => "Ошибка обновления service_status для '$service'",
                'fields' => $data
            ]);
        }

        EventManager::logParams(Event::INFO, self::class, $msg, [
            'service' => $service,
            'status'  => $status,
            'message' => $message,
            'details' => $details
        ]);
        return true;
    }

    /**
     * Получить статус одного сервиса.
     * @param string $service — имя сервиса (percoweb, firebird, ldap)
     */
    public function getStatus(string $service): ?ServiceStatus
    {
        $row = $this->db->first('service_status', ['service' => $service]);
        return $row ? new ServiceStatus($row) : null;
    }

    /**
     * Получить статусы всех сервисов.
     */
    public function getAll(): array
    {
        $rows = $this->db->get('service_status');
        return array_map(fn($row) => new ServiceStatus($row), $rows ?: []);
    }

    public function getAllKeyed(): array
    {
        $rows = $this->db->get('service_status');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['service']] = [
                'status'     => $row['status'],
                'message'    => $row['message'],
                'updated_at' => $row['updated_at'],
            ];
        }
        return $result;
    }

    /**
     * Удалить статус сервиса (например, при отключении).
     */
    public function deleteStatus(string $service): bool
    {
        $row = $this->db->first('service_status', ['service' => $service]);
        if (!$row) {
            throw new GeneralException("Сервис не найден", 404, [
                'detail' => "Нет статуса для сервиса '$service'"
            ]);
        }
        $success = $this->db->delete('service_status', ['service' => $service]);
        if (!$success) {
            throw new GeneralException("Не удалось удалить статус сервиса", 500, [
                'detail' => "Ошибка при удалении статуса для '$service'"
            ]);
        }
        EventManager::logParams(Event::INFO, self::class, "Удалён статус сервиса '$service'", [
            'service' => $service
        ]);
        return true;
    }
}