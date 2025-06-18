#!/usr/bin/env php
<?php
// bin/worker.php
define('_RUNKEY', 1);

require_once __DIR__ . '/../bootstrap.php';

use GIG\Core\Config;
use GIG\Core\Application;
use GIG\Domain\Services\BackgroundTaskManager;
use GIG\Domain\Entities\BackgroundTask;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Entities\Event;

$config = Config::get('settings');
$maxTasks = $config['max_tasks'] ?? 10;
$sleep = $config['refresh_time'] ?? 5;
$maxCycles = $config['max_cycles'] ?? 1;
$tasks = $config['background_task_types'] ?? [];

echo "[Worker] Старт обработчика фоновых процессов...\n";
$app = Application::getInstance();
$manager = new BackgroundTaskManager($app->getMysql());

$cycle = 0;
do {
    foreach ($tasks as $task) {
        try {
            $manager->updateTask($task->id, ['status' => 'running']);
            $manager->appendTaskLog($task->id, "Запуск задачи {$task->type}");

            // ==== ВАЖНО: Хендлеры задач ====
            $result = runTaskHandler($task, $manager);

            $manager->updateTask($task->id, [
                'status' => 'done',
                'result' => $result,
                'progress' => 100,
                'finished_at' => date('Y-m-d H:i:s')
            ]);
            $manager->appendTaskLog($task->id, "Задача выполнена успешно");
        } catch (\Throwable $e) {
            $manager->updateTask($task->id, [
                'status' => 'error',
                'result' => ['error' => $e->getMessage()],
                'finished_at' => date('Y-m-d H:i:s')
            ]);
            $manager->appendTaskLog($task->id, "Ошибка: {$e->getMessage()}");
        }
    }

    $cycle++;
    if ($maxCycles && $cycle >= $maxCycles) break;
    sleep($sleep);

} while (true);

echo "[Worker] Работа завершена.\n";
// exit(0);


/**
 * Диспатчер типов задач.
 * @param BackgroundTask $task
 * @param BackgroundTaskManager $manager
 * @return array $result
 */
function runTaskHandler(BackgroundTask $task, BackgroundTaskManager $manager): array
{
    switch ($task->type) {
        case 'sync_perco_web_directory':
            // TODO: бизнес-логика
            $manager->appendTaskLog($task->id, "Синхронизация справочников PERCo-Web...");
            return ['demo' => 'sync_perco_web_directory завершена'];
        case 'import_firebird_table':
            $manager->appendTaskLog($task->id, "Импорт из Firebird таблицы {$task->params['table']}");
            return ['demo' => 'import_firebird_table завершен'];
        case 'enrich_user_data':
            $manager->appendTaskLog($task->id, "Обогащение профиля пользователя...");
            return ['demo' => 'enrich_user_data завершено'];
        case 'export_csv':
            $manager->appendTaskLog($task->id, "Экспорт данных в CSV...");
            return ['demo' => 'export_csv завершён'];
        case 'cleanup_old_tasks':
            $manager->appendTaskLog($task->id, "Очистка старых задач...");
            return ['demo' => 'cleanup_old_tasks завершена'];
        default:
            // Плагины — скрипт по имени типа задачи
            $file = __DIR__ . "/tasks/{$task->type}.php";
            if (file_exists($file)) {
                return include $file;
            }
            throw new \RuntimeException("Неизвестный тип задачи: {$task->type}");
    }
}
