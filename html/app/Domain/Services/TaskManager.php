<?php
namespace GIG\Domain\Services;


defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Infrastructure\Persistence\SupervisorClient;
use GIG\Domain\Entities\Task;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Entities\Event;
use GIG\Core\Application;

class TaskManager
{
    protected DatabaseClientInterface $db;
    protected SupervisorClient $sv;
    protected string $container = 'worker';

    public function __construct(DatabaseClientInterface $db = null, SupervisorClient $sv = null)
    {
        $this->db = $db ?? Application::getInstance()->getMysqlClient();
        $this->sv = $sv ?? Application::getInstance()->getSupervisorClient();
    }

    public function getWorkers(): array{
        $list = $this->sv->getAllProcesses();
        $result = [];
        foreach ($list as $p) {
            $result[] = [
                'name'      => $p['name'] ?? '',
                'group'     => $p['group'] ?? '',
                'status'    => $p['statename'] ?? '',
                'pid'       => $p['pid'] ?? '',
                'desc'      => $p['description'] ?? ''
            ];
        }
        return $result;
    }

    public function startWorker(string $script): bool
    {
        try {
            $ok = $this->sv->start($script);
            if ($ok) EventManager::logParams(Event::INFO, self::class, "Задача $script успешно запущена");
            return $ok;
        } catch (GeneralException $e) {
            throw $e;
        }
    }

    public function stopWorker(string $script): bool
    {
        try {
            $ok = $this->sv->stop($script);
            if ($ok) EventManager::logParams(Event::INFO, self::class, "Задача $script успешно остановлена");
            return $ok;
        } catch (GeneralException $e) {
            throw $e;
        }
    }

    public function restartWorker(string $script): bool
    {
        try {
            $ok = $this->sv->restart($script);
            if ($ok) EventManager::logParams(Event::INFO, self::class, "Задача $script успешно перезапущена");
            return $ok;
        } catch (GeneralException $e) {
            throw $e;
        }
    }

    public function isWorkerRunning(string $script): bool
    {
        $all = $this->getWorkers();
        foreach ($all as $p) {
            if ($p['name'] === $script) {
                return $p['status'] === 'RUNNING';
            }
        }
        return false;
    }

    private function getWorkerStatus(string $script): string
    {
        if (!$script || !file_exists($script)) return 'not found';
        return $this->isWorkerRunning($script) ? 'running' : 'stopped';
    }

    /**
     * Создать новую фоновую задачу.
     */
    public function createTask(string $type, string $name, array $params = [], ?int $userId = null, string $executionType = 'ONCE', ?int $interval = null): Task
    {
        $data = [
            'type'              => $type,
            'name'              => $name,
            'status'            => 'PENDING',
            'params'            => json_encode($params, JSON_UNESCAPED_UNICODE),
            'progress'          => 0,
            'user_id'           => $userId,
            'execution_type'    => $executionType,
            'interval_minutes'  => $executionType === 'RECURRING' ? $interval : null,
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
        // file_put_contents(PATH_LOGS.'debug_perco.json', json_encode($rows));
        return array_map(function($row) {
            return new Task($this->decodeRow($row));
        }, $rows);
    }

    public function getRunnableTasks(int $limit = 50)
    {
        $rawTasks = $this->getTasks(['status' => ['PENDING', 'ERROR']], 200);

        $result = [];
        $now = time();

        foreach ($rawTasks as $task) {
            if ($task->execution_type === 'ONCE') {
                $result[] = $task;
            } elseif ($task->execution_type === 'RECURRING') {
                if ($task->last_run_at === null) {
                    $result[] = $task;
                } else {
                    $last = strtotime($task->last_run_at);
                    $interval = ($task->interval_minutes ?? 0) * 60;
                    if (($now - $last) >= $interval) {
                        $result[] = $task;
                    }
                }
            }

            if (count($result) >= $limit) break;
        }

        // file_put_contents(PATH_LOGS.'debug_perco.json', $result);
        return $result;
    }

    public function updateTask(int $id, array $fields): bool
    {
        // $row = $this->getTaskRowOrFail($id);

        // JSON-поля — преобразуем, если нужно
        foreach (['params', 'result', 'log'] as $field) {
            if (isset($fields[$field]) && is_array($fields[$field])) {
                $fields[$field] = json_encode($fields[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        $fields['updated_at'] = date('Y-m-d H:i:s');
        if (isset($fields['status']) && $fields['status'] === 'RUNNING') {
            $fields['last_run_at'] = date('Y-m-d H:i:s');
        }

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
            if (isset($row['interval_minutes'])) {
                $row['interval_minutes'] = (int) $row['interval_minutes'];
            }
            if (isset($row['last_run_at']) && $row['last_run_at'] === '0000-00-00 00:00:00') {
                $row['last_run_at'] = null;
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