<?php
define('_RUNKEY', 1);

defined('DS') or        define('DS', DIRECTORY_SEPARATOR);
defined('ABSPATH') or   define('ABSPATH', dirname( __DIR__) . DS);

session_start();

// Functions
if (file_exists(ABSPATH.'system'.DS.'functions.php')) {
    require_once ABSPATH.'system'.DS.'functions.php';
}
else{
	die('No required file (functions.php) found. Stopped.');
}

// Constants
if (file_exists(ABSPATH.'system'.DS.'defines.php')) {
    require_once ABSPATH.'system'.DS.'defines.php';
}
else{
	die('No required file (defines.php) found. Stopped.');
}

// Autoloader
$autoloaderFile = ABSPATH . 'system' . DS . 'Autoloader.php';
if (!file_exists($autoloaderFile)) {
    die('Critical error: Autoloader is missing.');
}
require_once $autoloaderFile;

$loader = new \GigReportServer\System\Autoloader;
$loader->register();

$loader->addNamespace(APPLICATION . '\System', 						PATH_SYSTEM);
$loader->addNamespace(APPLICATION . '\System\Exceptions',	        PATH_EXCEPTIONS);
$loader->addNamespace(APPLICATION . '\System\Engine', 				PATH_ENGINE);
$loader->addNamespace(APPLICATION . '\System\Http', 				PATH_HTTP);
$loader->addNamespace(APPLICATION . '\Pages\Controllers', 			PATH_CONTROLLERS);
$loader->addNamespace(APPLICATION . '\Pages\Views',					PATH_VIEWS);
$loader->addNamespace(APPLICATION . '\Pages\Models',				PATH_MODELS);
$loader->addNamespace(APPLICATION . '\API',							PATH_API);

