<?php
require_once __DIR__ . '/../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', '/var/www/logs/worker/worker_error.log');

use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Application;
use GIG\Domain\Services\ServiceStatusManager;
use GIG\Domain\Services\TaskManager;
use GIG\Domain\Services\PercoManager;

$app = Application::getInstance();
$taskManager = new TaskManager($app->getMysqlClient());
$statusManager = new ServiceStatusManager($app->getMysqlClient());

$config = $app->getConfig();
$services = $config['services'] ?? [];
$settings = $config['settings'] ?? [];
$delay = $settings['worker_delay'] ?? 30; // секунд

while (true) {
    foreach ($services as $serviceName => $serviceCfg) {
        try {
            $client = $app->getServiceByName($serviceName);
            if (!$client) {
                throw new GeneralException("Сервис '$serviceName' не реализован или не внедрен.");
            }
            if (!method_exists($client, 'checkStatus')) {
                throw new GeneralException("Клиент '$serviceName' не реализует checkStatus().");
            }
            $result = $client->checkStatus();
            $status = $result['status'] ?? 'unknown';
            $message = $result['message'] ?? '';
            $detail = $result['detail'] ?? null;
        } catch (\Throwable $e) {
            $status = 'fail';
            $message = $e->getMessage();
            $detail = $e->getTraceAsString();
        }

        try {
            $statusManager->setStatus($serviceName, $status, $message, $detail);
        } catch (\Throwable $e) {
            if ($e instanceof GeneralException) {
                var_dump($e->getExtra());
            } else {
                echo $e->getMessage();
            }
        }

        if ($status !== 'ok') echo "$serviceName: $status ($message)\n";
    }

    $tasks = $taskManager->getRunnableTasks(50);
    if (!empty($tasks)) {
        $lines = array_map(fn($task) => '- ' . ($task->name ?? $task->type), $tasks);
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] RUNNABLE TASKS:\n" . implode(";\n", $lines) . ".\n";
        file_put_contents(PATH_LOGS . 'worker_debug.log', $message, FILE_APPEND);
    }

    foreach ($tasks as $task) {
        $taskId = $task->id;
        try {
            $taskManager->updateTask($taskId, [
                'status' => 'RUNNING',
                'progress' => 0,
                'last_run_at' => date('Y-m-d H:i:s'),
            ]);
            $taskManager->appendTaskLog($taskId, "Запуск задачи {$task->type}");

            switch ($task->type) {
                case 'sync_divisions':
                    $result = syncDivisions($app);
                    break;
                case 'sync_positions':
                    $result = syncPositions($app);
                    break;
                default:
                    throw new GeneralException("Неизвестный тип задачи: {$task->type}");
            }

            $taskManager->updateTask($taskId, [
                'status' => $task->execution_type === 'ONCE' ? 'DONE' : 'PENDING',
                'progress' => 1,
                'result' => $result,
            ]);
            $taskManager->appendTaskLog($taskId, "Задача успешно выполнена.");
        } catch (\Throwable $e) {
            $taskManager->updateTask($taskId, [
                'status' => 'ERROR',
                'progress' => 1,
                'log' => array_merge($task->log, [[
                    'ts' => date('Y-m-d H:i:s'),
                    'msg' => $e->getMessage()
                ]]),
            ]);
            $taskManager->appendTaskLog($taskId, "Ошибка: " . $e->getMessage());
        }
    }

    sleep($delay);
}

function syncDivisions($app) {
    $client = new PercoManager();
    $db = $app->getMysqlClient();
    $statusManager = new ServiceStatusManager($db);

    $statusManager->setStatus('perco_web', 'busy', 'Синхронизация подразделений начата');

    try {
        $divisions = $client->fetchAllDivisions();

        file_put_contents(PATH_LOGS . 'worker_debug.log', "DIVISIONS: " . count($divisions) . ' записей получено.' . PHP_EOL, FILE_APPEND);

        $rows = [];
        $fields = ['id', 'name', 'parent_id', 'external_code'];
        foreach ($divisions as $item) {
            $rows[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'parent_id' => $item['parent_id'] ?? null,
                'external_code' => $item['comment'] ?? null,
            ];
        }

        $db->upsertMany('division', $fields, $rows);
        $statusManager->setStatus('perco_web', 'ok', 'Синхронизация подразделений завершена');
        return ['count' => count($rows)];
    } catch (\Throwable $e) {
        $statusManager->setStatus('perco_web', 'fail', $e->getMessage(), $e->getTraceAsString());
        throw $e;
    }
}

function syncPositions($app) {
    $client = new PercoManager();
    $db = $app->getMysqlClient();
    $positions = $client->fetchAllPositions();

    file_put_contents(PATH_LOGS . 'worker_debug.log', "POSITIONS: " . count($positions) . ' записей получено.' . PHP_EOL, FILE_APPEND);

    $rows = [];
    $fields = ['id', 'name', 'external_code'];
    foreach ($positions as $item) {
        $rows[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'external_code' => $item['comment'] ?? null,
        ];
    }

    $db->upsertMany('position', $fields, $rows);
    return ['count' => count($rows)];
}

function syncCompanies($app) {
    $client = new PercoManager();
    $db = $app->getMysqlClient();
    $companies = $client->fetchAllCompanies();

    file_put_contents(PATH_LOGS . 'worker_debug.log', "COMPANIES: " . count($companies) . ' записей получено.' . PHP_EOL, FILE_APPEND);

    $rows = [];
    $fields = ['id', 'name', 'external_code'];
    foreach ($companies as $item) {
        $rows[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'external_code' => $item['comment'] ?? null,
        ];
    }

    $db->upsertMany('position', $fields, $rows);
    return ['count' => count($rows)];
}
