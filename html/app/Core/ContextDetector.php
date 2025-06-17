<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class ContextDetector
{
    public const CONTEXT_CLI = 'cli';
    public const CONTEXT_API = 'api';
    public const CONTEXT_UI = 'ui';

    /**
     * @return string 'api', 'ui', 'cli', 'unknown'
     */
    public static function detect()
    {
        if(php_sapi_name() === 'cli') return self::CONTEXT_CLI;
        if(
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0) ||
            (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
        ) {
            return self::CONTEXT_API;
        }
        if(isset($_SERVER['REQUEST_METHOD'])) return self::CONTEXT_UI;

        return 'unknown';
    }
}