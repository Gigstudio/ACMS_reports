<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Domain\Entities\Task;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Entities\Event;

class TaskManager
{
    protected DatabaseClientInterface $db;

    public function __construct(DatabaseClientInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Создать новую фоновую задачу.
     */
    public function createTask(string $type, array $params = [], ?int $userId = null): Task
    {
        $data = [
            'type'       => $type,
            'status'     => 'PENDING',
            'params'     => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress'   => 0,
            'user_id'    => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $success = $this->db->insert('background_task', $data);
        if (!$success) {
            throw new GeneralException("Ошибка создания фоновой задачи", 500, [
                'detail' => "Не удалось вставить запись в таблицу background_task",
                'data' => $data
            ]);
        }

        $id = $this->db->lastInsertId();
        EventManager::logParams(Event::INFO, self::class, "Создана фоновая задача #$id типа $type", ['id' => $id, 'type' => $type, 'user_id' => $userId]);

        return $this->getTask($id);
    }

    public function getTask(int $id): ?Task
    {
        $row = $this->getTaskRowOrFail($id);
        return new Task($row);
    }

    public function getTasks(array $filter = [], int $limit = 100): array
    {
        $rows = $this->db->get('background_task', $filter, ['*'], $limit);
        return array_map(function($row) {
            return new Task($this->decodeRow($row));
        }, $rows);
    }

    public function updateTask(int $id, array $fields): bool
    {
        $row = $this->getTaskRowOrFail($id);

        // JSON-поля — преобразуем, если нужно
        foreach (['params', 'result', 'log'] as $field) {
            if (isset($fields[$field]) && is_array($fields[$field])) {
                $fields[$field] = json_encode($fields[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        $fields['updated_at'] = date('Y-m-d H:i:s');

        if (isset($fields['status']) && in_array($fields['status'], ['DONE', 'ERROR', 'ABORTED'])) {
            $fields['finished_at'] = date('Y-m-d H:i:s');
        }

        $success = $this->db->update('background_task', $fields, ['id' => $id]);
        if (!$success) {
            throw new GeneralException("Не удалось обновить задачу", 500, [
                'detail' => "Ошибка при обновлении задачи #$id",
                'fields' => $fields
            ]);
        }

        EventManager::logParams(Event::INFO, self::class, "Обновлены поля задачи #$id", [
            'id' => $id,
            'fields' => $fields
        ]);
        return true;
    }

    public function appendTaskLog(int $id, string $logEntry): bool
    {
        $row = $this->getTaskRowOrFail($id);

        $logs = [];
        if (!empty($row['log'])) {
            if (is_string($row['log'])) {
                $decoded = json_decode($row['log'], true);
                $logs = is_array($decoded) ? $decoded : [];
            } elseif (is_array($row['log'])) {
                $logs = $row['log'];
            }
        }
        $logs[] = [
            'ts'   => date('Y-m-d H:i:s'),
            'msg'  => $logEntry
        ];

        $success = $this->db->update('background_task', [
            'log'        => json_encode($logs, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        if (!$success) {
            throw new GeneralException("Ошибка при добавлении лога задачи", 500, [
                'detail' => "Ошибка при добавлении лога к задаче #$id"
            ]);
        }

        EventManager::logParams(Event::INFO, self::class, "Добавлен лог к задаче #$id", ['id' => $id, 'log' => $logEntry]);
        return true;
    }

    public function deleteTask(int $id): bool
    {
        $this->getTaskRowOrFail($id);
        $success = $this->db->delete('background_task', ['id' => $id]);
        if (!$success) {
            throw new GeneralException("Ошибка при удалении задачи", 500, [
                'detail' => "Не удалось удалить задачу #$id"
            ]);
        }
        EventManager::logParams(Event::INFO, self::class, "Удалена фоновая задача #$id", ['id' => $id]);
        return true;
    }

    // -- Утилита для автодекодирования json-полей --
    protected function decodeRow(array $row): array
    {
        foreach (['params', 'result', 'log'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : $row[$field];
            }
        }
        return $row;
    }
    protected function getTaskRowOrFail(int $id): array
    {
        $row = $this->db->first('background_task', ['id' => $id]);
        if (!$row) {
            throw new GeneralException("Задача не найдена", 404, [
                'detail' => "Нет задачи с ID $id"
            ]);
        }
        return $this->decodeRow($row);
    }    
}