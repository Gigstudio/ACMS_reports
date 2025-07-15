<?php

// define('_RUNKEY', 1);

require_once __DIR__ . '/../bootstrap.php';

use GIG\Core\ErrorHandler;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Application;
use GIG\Domain\Services\ServiceStatusManager;
use GIG\Domain\Services\BackgroundTaskManager;

$app = Application::getInstance();
$taskManager = new BackgroundTaskManager($app->getMysqlClient());
$statusManager = new ServiceStatusManager($app->getMysqlClient());

$config = $app->getConfig();
$services = $config['services'] ?? [];
$settings = $config['settings'] ?? [];

foreach ($services as $serviceName => $serviceCfg) {
    try {
        $client = $app->getServiceByName($serviceName);
        if(!$client){
            throw new GeneralException("Сервис '$serviceName' не реализован.");
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

    try{
        $statusManager->setStatus($serviceName, $status, $message, $detail);
    } catch (\Throwable $e){
        if($e instanceof GeneralException){
            var_dump($e->getExtra());
        }
        // echo $e->getMessage();
    }
    echo "$serviceName: $status ($message)\n";
}