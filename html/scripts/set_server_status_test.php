<?php
// define('_RUNKEY', 1);
require_once __DIR__ . '/../bootstrap.php';

use GIG\Domain\Services\ServiceStatusManager;
use GIG\Core\Application;

$app = Application::getInstance();
$manager = new ServiceStatusManager($app->getMysqlClient());

$manager->setStatus('PERCo-Web', 'ok', 'PERCo-Web доступен');
$manager->setStatus('PERCo-S20', 'fail', 'Firebird не отвечает');
$manager->setStatus('LDAP', 'warn', 'LDAP: проблемы с соединением');
echo "Статусы установлены.\n";
