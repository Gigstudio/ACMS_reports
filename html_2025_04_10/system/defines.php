<?php
defined('_RUNKEY') or die;

$parts = explode(DS, ABSPATH);

// Defines.
defined('APPLICATION') or 		define('APPLICATION', 'GigReportServer');

defined('PATH_ROOT') or 		    define('PATH_ROOT', implode(DS, $parts));
defined('PATH_CONFIG') or 		define('PATH_CONFIG', PATH_ROOT.'config'.DS);
defined('PATH_API') or 			define('PATH_API', PATH_ROOT.'API'.DS);
defined('PATH_SYSTEM') or 		define('PATH_SYSTEM', PATH_ROOT.'system'.DS);
defined('PATH_ENGINE') or 		define('PATH_ENGINE', PATH_SYSTEM.'engine'.DS);
defined('PATH_LIBRARIES') or 	define('PATH_LIBRARIES', PATH_SYSTEM.'libraries'.DS);
defined('PATH_HTTP') or 		    define('PATH_HTTP', PATH_SYSTEM.'http'.DS);
defined('PATH_NETWORK') or       define('PATH_NETWORK', PATH_SYSTEM.'network'.DS);
defined('PATH_MODULES') or 		define('PATH_MODULES', PATH_ENGINE.'modules'.DS);
defined('PATH_PAGES') or 		define('PATH_PAGES', PATH_ROOT.'pages'.DS);
defined('PATH_CONTROLLERS') or 	define('PATH_CONTROLLERS', PATH_PAGES.'controllers'.DS);
defined('PATH_VIEWS') or 		define('PATH_VIEWS', PATH_PAGES.'views'.DS);
defined('PATH_LAYOUTS') or 		define('PATH_LAYOUTS', PATH_PAGES.'layouts'.DS);
defined('PATH_MODELS') or 		define('PATH_MODELS', PATH_PAGES.'models'.DS);
defined('PATH_STORAGE') or 		define('PATH_STORAGE', PATH_ROOT.'storage'.DS);
defined('PATH_LOGS') or 		    define('PATH_LOGS', PATH_STORAGE.'logs'.DS);
// defined('PATH_SESSION') or 		define('PATH_SESSION', PATH_STORAGE.'sessions'.DS);
// defined('PATH_CACHE') or 		define('PATH_CACHE', PATH_STORAGE.'cache'.DS);
// defined('PATH_UPLOAD') or 		define('PATH_UPLOAD', PATH_STORAGE.'upload'.DS);
// defined('PATH_DOWNLOAD') or 	define('PATH_DOWNLOAD', PATH_STORAGE.'download'.DS);
// defined('PATH_LANGUAGE') or 	define('PATH_LANGUAGE', PATH_ROOT.APPLICATION.'language'.DS);
// defined('PATH_SOURCE') or 		define('PATH_SOURCE', PATH_ROOT.'source'.DS);
// defined('PATH_DATA') or 		define('PATH_DATA', PATH_ROOT.'data'.DS);
defined('PATH_ASSETS') or 		define('PATH_ASSETS', PATH_ROOT.'siteroot/assets'.DS);
// defined('PATH_FONTS') or 		define('PATH_FONTS', PATH_ASSETS.'fonts'.DS);
// defined('PATH_IMAGES') or 		define('PATH_IMAGES', PATH_ASSETS.'images'.DS);

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
// defined('MAIN_LOGO_FILE') or 	define('MAIN_LOGO_FILE', 'ttype.png');
// defined('SITE_ICON_FILE') or 	define('SITE_ICON_FILE', 'gigicon.png');

defined('ENVIRONMENT') or 		define('ENVIRONMENT', 'development');
// defined('ENVIRONMENT') or 		define('ENVIRONMENT', 'deploy');
