<?php
/**
 * Менеджер фоновых процессов: мониторинг сервисов
 */
require_once __DIR__ . '/../bootstrap.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/logs/worker/service_monitor_error.log');

use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Application;
use GIG\Domain\Services\ServiceStatusManager;

$app = Application::getInstance();

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
            file_put_contents(PATH_LOGS . 'service_monitor.log',
                '[' . date('Y-m-d H:i:s') . "] setStatus error: " . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }

        if ($status !== 'ok') echo "$serviceName: $status ($message)\n";
    }

    sleep($delay);
}
