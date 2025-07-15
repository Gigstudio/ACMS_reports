#!/usr/bin/env php
<?php
// bin/worker.php
define('_RUNKEY', 1);

require_once __DIR__ . '/../bootstrap.php';

use GIG\Core\Config;
use GIG\Core\Application;
use GIG\Domain\Services\BackgroundTaskManager;
use GIG\Domain\Services\ServiceStatusManager;
use GIG\Domain\Entities\BackgroundTask;

$app = Application::getInstance();
$taskManager = new BackgroundTaskManager($app->getMysqlClient());
$statusManager = new ServiceStatusManager($app->getMysqlClient());

$config = Config::get('settings');
$maxTasks = (int)$config['max_tasks'] ?? 10;
$sleep = (int)$config['refresh_time'] ?? 5;
$maxCycles = (int)$config['max_cycles'] ?? 1;
$supportedTaskTypes  = $config['background_task_types'] ?? [];

$serviceStatusProviders = [
    'percoweb' => function() {
        // Здесь реальный health-check PERCo-Web!
        // Пример: делаем curl/HTTP-запрос к API, парсим ответ
        $url = 'http://percoweb.local/api/ping';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || $httpCode !== 200) {
            return ['status' => 'fail', 'message' => 'PERCo-Web: нет связи'];
        }

        // Можно анализировать body или статус-флаг в ответе
        $data = json_decode($response, true);
        if (!isset($data['status']) || $data['status'] !== 'ok') {
            return ['status' => 'warn', 'message' => 'PERCo-Web: ответ невалиден'];
        }

        // Дополнительные проверки (блокировка/фоновая обработка)
        if (!empty($data['busy'])) {
            return ['status' => 'busy', 'message' => 'PERCo-Web: выполняется обработка данных'];
        }
        // Здесь же можно проверить наличие предупреждений
        if (!empty($data['warnings'])) {
            return ['status' => 'warn', 'message' => 'PERCo-Web: ' . implode('; ', $data['warnings'])];
        }

        return ['status' => 'ok', 'message' => 'PERCo-Web: работает'];
    },
    'firebird' => function() use ($app, $taskManager) {
        try{
            /** @var \GIG\Infrastructure\Persistence\FirebirdClient $client */
            $client = $app->getFirebirdClient();

            $busyTypes = ['import_firebird_table', 'sync_firebird_data', /* другие, если есть */];
            $busyTasks = $taskManager->getTasks([
                'type'   => $busyTypes,
                'status' => ['pending', 'running']
            ], 1); // достаточно одной

            if (!empty($busyTasks)) {
                return [
                    'status' => 'busy',
                    'message' => 'Firebird: выполняется фоновая задача (импорт/синхронизация)'
                ];
            }

            if ($client->tableExists('STAFF')) {
                return ['status' => 'fail', 'message' => 'Firebird: не отвечает на запрос'];
            }
            // 3. Можно добавить анализ ошибок/логов для статуса warn
            // if ($есть_ошибки_или_аномалии) {
            //     return ['status' => 'warn', 'message' => 'Firebird: есть предупреждения/ошибки'];
            // }

            return ['status' => 'ok', 'message' => 'Firebird: работает'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Firebird: ' . $e->getMessage()];
        }
    },
    'ldap' => function() {
        if (!checkServiceConnection('ldap')) {
            return ['status' => 'fail', 'message' => 'LDAP: нет связи'];
        } elseif (isServiceBusy('ldap')) {
            return ['status' => 'busy', 'message' => 'LDAP: выполняется обработка данных'];
        } elseif (serviceHasWarning('ldap')) {
            return ['status' => 'warn', 'message' => 'LDAP: имеются предупреждения'];
        } else {
            return ['status' => 'ok', 'message' => 'LDAP: работает'];
        }
    },
    // Добавлять новые сервисы — только здесь, больше нигде
];

echo "[Worker] Старт обработчика фоновых процессов...\n";

$cycle = 0;
do {
    $tasks = $taskManager->getTasks([
        'status' => 'pending',
        'type'   => $supportedTaskTypes
    ], $maxTasks);

    if (!$tasks) {
        echo "[Worker] Нет задач для обработки. Ожидание...\n";
        sleep($sleep);
        $cycle++;
        if ($maxCycles && $cycle >= $maxCycles) break;
        continue;
    }

    foreach ($tasks as $task) {
        try {
            $taskManager->updateTask($task->id, ['status' => 'running']);
            $taskManager->appendTaskLog($task->id, "Запуск задачи {$task->type}");

            $result = runTaskHandler($task, $taskManager, $statusManager, $serviceStatusProviders);

            $taskManager->updateTask($task->id, [
                'status' => 'done',
                'result' => $result,
                'progress' => 100,
                'finished_at' => date('Y-m-d H:i:s')
            ]);
            $taskManager->appendTaskLog($task->id, "Задача выполнена успешно");
        } catch (\Throwable $e) {
            $taskManager->updateTask($task->id, [
                'status' => 'error',
                'result' => ['error' => $e->getMessage()],
                'finished_at' => date('Y-m-d H:i:s')
            ]);
            $taskManager->appendTaskLog($task->id, "Ошибка: {$e->getMessage()}");
        }
    }

    $cycle++;
    if ($maxCycles && $cycle >= $maxCycles) break;
    sleep($sleep);

} while (true);

echo "[Worker] Работа завершена.\n";
exit(0);


/**
 * Диспатчер типов задач.
 * @param BackgroundTask $task
 * @param BackgroundTaskManager $taskManager
 * @param ServiceStatusManager $statusManager
 * @param array $serviceStatusProviders
 * @return array $result
 */
function runTaskHandler(
    BackgroundTask $task, 
    BackgroundTaskManager $taskManager, 
    ServiceStatusManager $statusManager, 
    array $serviceStatusProviders
): array
{
    switch ($task->type) {
        case 'service_status_check':
            $service = $task->params['service'] ?? null;
            if (!$service) throw new \RuntimeException("Не задан параметр 'service'");

            $result = getServiceStatus($service, $serviceStatusProviders);

            $statusManager->setStatus($service, $result['status'], $result['message']);
            $taskManager->appendTaskLog($task->id, "Статус сервиса $service: {$result['status']} ({$result['message']})");
            return ['service' => $service] + $result;

        case 'sync_perco_web_directory':
            // TODO: бизнес-логика
            $taskManager->appendTaskLog($task->id, "Синхронизация справочников PERCo-Web...");
            return ['demo' => 'sync_perco_web_directory завершена'];

        case 'import_firebird_table':
            $taskManager->appendTaskLog($task->id, "Импорт из Firebird таблицы {$task->params['table']}");
            return ['demo' => 'import_firebird_table завершен'];

        case 'enrich_user_data':
            $taskManager->appendTaskLog($task->id, "Обогащение профиля пользователя...");
            return ['demo' => 'enrich_user_data завершено'];

        case 'export_csv':
            $taskManager->appendTaskLog($task->id, "Экспорт данных в CSV...");
            return ['demo' => 'export_csv завершён'];

        case 'cleanup_old_tasks':
            $taskManager->appendTaskLog($task->id, "Очистка старых задач...");
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

/**
 * Универсальная функция проверки статуса сервиса.
 * @param string $service
 * @return array ['status' => ..., 'message' => ...]
 */
function getServiceStatus(string $service, array $serviceStatusProviders): array
{
    if (isset($serviceStatusProviders[$service])) {
        return $serviceStatusProviders[$service]();
    }
    return ['status' => 'unknown', 'message' => 'Сервис не определён'];
}

function checkServiceConnection(string $service): bool
{
    // TODO: Реальные проверки — ping, curl, connect и т.п.
    switch ($service) {
        case 'percoweb': return true;
        case 'firebird': return true;
        case 'ldap':     return true;
        default:         return false;
    }
}

function isServiceBusy(string $service): bool
{
    // TODO: Реализовать проверку фоновых процессов, блокировок, занятости очереди
    return false;
}

function serviceHasWarning(string $service): bool
{
    // TODO: Реализовать анализ логов, ошибок, завершения задач
    return false;
}