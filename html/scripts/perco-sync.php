<?php
/**
 * Синхронизация справочников PERCo-Web
 */
require_once __DIR__ . '/../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/logs/worker/perco_sync_error.log');

use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Application;
use GIG\Domain\Services\ServiceStatusManager;
use GIG\Domain\Services\TaskManager;
use GIG\Domain\Services\PercoManager;

$app = Application::getInstance();

$taskManager = new TaskManager($app->getMysqlClient());
$statusManager = new ServiceStatusManager($app->getMysqlClient());

$config = $app->getConfig();
$settings = $config['settings'] ?? [];
$delay = $settings['worker_delay'] ?? 30; // секунд

const ALLOWED_TYPES = ['sync_divisions', 'sync_positions'];

while (true) {
    $tasks = array_values(array_filter(
        $taskManager->getRunnableTasks(50),
        fn($t) => in_array($t->type, ALLOWED_TYPES, true)
    ));

    // if (!empty($tasks)) {
        // $lines = array_map(fn($task) => '- ' . ($task->name ?? $task->type), $tasks);
        // file_put_contents(PATH_LOGS . 'perco_sync.log',
        //     '[' . date('Y-m-d H:i:s') . "] RUNNABLE:\n" . implode(";\n", $lines) . ".\n",
        //     FILE_APPEND
        // );    
    // }

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
                    $result = syncDivisions($app, $statusManager);
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

function syncDivisions(Application $app, ServiceStatusManager $statusManager) {
    $client = new PercoManager();
    $db = $app->getMysqlClient();

    $statusManager->setStatus('perco_web', 'busy', 'Синхронизация подразделений начата');

    try {
        $divisions = $client->fetchAllDivisions();

        // file_put_contents(PATH_LOGS . 'worker_debug.log', "DIVISIONS: " . count($divisions) . ' записей получено.' . PHP_EOL, FILE_APPEND);

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

    // file_put_contents(PATH_LOGS . 'worker_debug.log', "POSITIONS: " . count($positions) . ' записей получено.' . PHP_EOL, FILE_APPEND);

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
