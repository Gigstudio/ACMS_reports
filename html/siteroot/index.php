<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use GIG\Domain\Services\EventManager;
use GIG\Core\FileEventLogger;
use GIG\Core\ErrorHandler;
use GIG\Core\DbSchemaManager;
use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Core\Application;
use GIG\Infrastructure\Repository\EventRepository;
use GIG\Core\Config;

// 1. Подключаем логгер событий
EventManager::setLogger(new FileEventLogger(PATH_LOGS . 'events.log'));

// 2. Регистрируем ErrorHandler
ErrorHandler::register();

// 3. Проверяем и, если нужно, инициализируем/мигрируем БД
$db = new MySQLClient();
DbSchemaManager::checkAndMigrateStatic($db);
EventManager::setRepository(new EventRepository($db));

// 4. Запускаем ядро приложения
$app = Application::getInstance();
define('APP_READY', true);

$app->run();
