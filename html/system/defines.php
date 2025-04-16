<?php
defined('_RUNKEY') or die;

$parts = explode(DS, ABSPATH);

// Defines.
defined('APPLICATION') or 		define('APPLICATION', 'GigReportServer');

defined('PATH_ROOT') or 		    define('PATH_ROOT', implode(DS, $parts));
defined('PATH_CONFIG') or 		define('PATH_CONFIG', PATH_ROOT.'config'.DS);
defined('PATH_API') or 			define('PATH_API', PATH_ROOT.'API'.DS);
defined('PATH_SYSTEM') or 		define('PATH_SYSTEM', PATH_ROOT.'system'.DS);
defined('PATH_EXCEPTIONS') or    define('PATH_EXCEPTIONS', PATH_SYSTEM.'exceptions'.DS);
defined('PATH_ENGINE') or 		define('PATH_ENGINE', PATH_SYSTEM.'engine'.DS);
defined('PATH_HTTP') or 		    define('PATH_HTTP', PATH_SYSTEM.'http'.DS);
// defined('PATH_NETWORK') or       define('PATH_NETWORK', PATH_SYSTEM.'network'.DS);
// defined('PATH_MODULES') or 		define('PATH_MODULES', PATH_ENGINE.'modules'.DS);
// defined('PATH_LIBRARIES') or 	define('PATH_LIBRARIES', PATH_SYSTEM.'libraries'.DS);
defined('PATH_PAGES') or 		define('PATH_PAGES', PATH_ROOT.'pages'.DS);
defined('PATH_MODELS') or 		define('PATH_MODELS', PATH_PAGES.'models'.DS);
defined('PATH_VIEWS') or 		define('PATH_VIEWS', PATH_PAGES.'views'.DS);
defined('PATH_CONTROLLERS') or 	define('PATH_CONTROLLERS', PATH_PAGES.'controllers'.DS);
defined('PATH_LAYOUTS') or 		define('PATH_LAYOUTS', PATH_PAGES.'layouts'.DS);
defined('PATH_STORAGE') or 		define('PATH_STORAGE', PATH_ROOT.'storage'.DS);
defined('PATH_LOGS') or 		    define('PATH_LOGS', PATH_STORAGE.'logs'.DS);
defined('PATH_ASSETS') or 		define('PATH_ASSETS', PATH_ROOT.'siteroot/assets'.DS);

defined('PROTOCOL') or           define('PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://');

$port = $_SERVER['SERVER_PORT'];
$host = $_SERVER['SERVER_NAME'];
$isStandardPort = ($port == 80 && PROTOCOL === 'http://') || ($port == 443 && PROTOCOL === 'https://');
$hostWithPort = $isStandardPort ? $host : "$host:$port";
$home = PROTOCOL.$hostWithPort.'/';
defined('HOME_URL') or 			define('HOME_URL', rtrim($home, '/') . '/');

$parsedUrl = parse_url(HOME_URL, PHP_URL_PATH);
$parsedUrl = rtrim($parsedUrl, '/');

defined('SITE_SHORT_NAME') or    define('SITE_SHORT_NAME', basename($parsedUrl) ?: 'home');

// defined('ENVIRONMENT') or 		define('ENVIRONMENT', 'development');
