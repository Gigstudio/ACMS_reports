<?php
define('DS',DIRECTORY_SEPARATOR);

if (!defined('ABSPATH')){
	define('ABSPATH',dirname( __DIR__) . DS );
}

error_reporting(E_ALL);
ini_set( 'display_errors','1');

if (!ini_get('date.timezone')) {
	date_default_timezone_set('UTC');
}

// PREPAREING SERVER
if (!isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];

	if (isset($_SERVER['QUERY_STRING'])) {
		$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
}

if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
}

// Check if SSL
$_SERVER['HTTPS'] = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);

// Check IP if forwarded IP
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['REMOTE_ADDR'] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

$initFile = ABSPATH . 'system' . DS . 'init.php';
if (!file_exists($initFile)) {
    die('No required file (init.php) found. Stopped.');
}
require_once $initFile;

// Starting app
use GigReportServer\System\Engine\ErrorHandler;
use GigReportServer\System\Engine\Config;
use GigReportServer\System\Engine\Application;

ErrorHandler::register();
$config = new Config();
$app = new Application($config);
$app -> run();
