<?php
defined('_RUNKEY') or die;

// Константы
defined('APPLICATION') or           define('APPLICATION', 'GIG');

// Пути
defined('PATH_ROOT') or             define('PATH_ROOT', dirname(__DIR__) . DS);
defined('PATH_CONFIG') or           define('PATH_CONFIG', PATH_ROOT . 'config' . DS);
defined('PATH_STORAGE') or          define('PATH_STORAGE', PATH_ROOT . 'storage' . DS);
defined('PATH_LOGS') or             define('PATH_LOGS', PATH_STORAGE . 'logs' . DS);

defined('PATH_APP') or              define('PATH_APP', PATH_ROOT . 'app' . DS);
defined('PATH_CORE') or             define('PATH_CORE', PATH_APP . 'Core' . DS);
defined('PATH_CONTRACTS') or        define('PATH_CONTRACTS', PATH_APP . 'Contracts' . DS);

defined('PATH_DOMAIN') or           define('PATH_DOMAIN', PATH_APP . 'Domain' . DS);
defined('PATH_ENTITIES') or         define('PATH_ENTITIES', PATH_DOMAIN . 'Entities' . DS);
defined('PATH_INFRASTRUCTURE') or   define('PATH_INFRASTRUCTURE', PATH_APP . 'Infrastructure' . DS);
defined('PATH_REPOSITORY') or       define('PATH_REPOSITORY', PATH_INFRASTRUCTURE . 'Repository' . DS);
defined('PATH_CLIENTS') or          define('PATH_CLIENTS', PATH_INFRASTRUCTURE . 'Clients' . DS);
defined('PATH_PERSISTENCE') or      define('PATH_PERSISTENCE', PATH_INFRASTRUCTURE . 'Persistence' . DS);

defined('PATH_PRESENTATION') or     define('PATH_PRESENTATION', PATH_APP . 'Presentation' . DS);
defined('PATH_VIEWS') or            define('PATH_VIEWS', PATH_PRESENTATION . 'View' . DS);
defined('PATH_CONTROLLERS') or      define('PATH_CONTROLLERS', PATH_PRESENTATION . 'Controller' . DS);

defined('PATH_API') or              define('PATH_API', PATH_ROOT . 'Api' . DS);
defined('PATH_API_CONTROLLERS') or  define('PATH_API_CONTROLLERS', PATH_API . 'Controller' . DS);

defined('PATH_SITEROOT') or         define('PATH_SITEROOT', PATH_ROOT . 'siteroot' . DS);
defined('PATH_ASSETS') or           define('PATH_ASSETS', PATH_SITEROOT . 'assets' . DS);

// Веб-режим — только если есть $_SERVER и сценарий не CLI
if (PHP_SAPI !== 'cli' && !empty($_SERVER['SERVER_NAME'])) {
    defined('PROTOCOL') or define('PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://');
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    $host = $_SERVER['SERVER_NAME'];
    $isStandardPort = ($port == 80 && PROTOCOL === 'http://') || ($port == 443 && PROTOCOL === 'https://');
    $hostWithPort = $isStandardPort ? $host : "$host:$port";
    $home = PROTOCOL . $hostWithPort . '/';
    defined('HOME_URL') or define('HOME_URL', rtrim($home, '/') . '/');
    $parsedUrl = parse_url(HOME_URL, PHP_URL_PATH);
    $parsedUrl = rtrim($parsedUrl, '/');
    defined('SITE_SHORT_NAME') or define('SITE_SHORT_NAME', basename($parsedUrl) ?: 'home');
} else {
    defined('PROTOCOL') or define('PROTOCOL', 'cli://');
    defined('HOME_URL') or define('HOME_URL', '');
    defined('SITE_SHORT_NAME') or define('SITE_SHORT_NAME', 'cli');
}

$devMode = false;
$initFile = PATH_CONFIG . 'init.json';

if (file_exists($initFile)) {
    $raw = file_get_contents($initFile);
    if ($raw) {
        $json = json_decode($raw, true);
        if (
            isset($json['settings']['debug_mode'])
            && in_array(strtolower($json['settings']['debug_mode']), ['debug', 'development', 'dev', 'on', '1'], true)
        ) {
            $devMode = true;
        }
    }
}
defined('DEV_MODE') or define('DEV_MODE', $devMode);