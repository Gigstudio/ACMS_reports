<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;
use GigReportServer\Pages\Controllers\MessageController;

class ErrorHandler
{
    protected static int $eventClass = Event::EVENT_ERROR;

    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        self::$eventClass = $errno === E_USER_NOTICE ? Event::EVENT_INFO : Event::EVENT_WARNING;
        $message = self::composeMessage(
            $errno, 
            $errstr, 
            pathinfo($errfile, PATHINFO_FILENAME), 
            $errline
        );
        self::registerEvent($message);
    }

    public static function handleException(\Throwable $e): void
    {
        self::$eventClass = Event::EVENT_ERROR;
        $message = self::composeMessage(
            $e->getCode(), 
            $e instanceof GeneralException ? $e->getExtra()['detail'] : $e->getMessage(), 
            pathinfo($e->getFile(), PATHINFO_FILENAME), 
            $e->getLine()
        );
        self::registerEvent($message);
        self::dispatch($e->getCode(), $e->getMessage());
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
    }

    private static function composeMessage(int $errno, string $errstr, string $errfile, int $errline){
        return sprintf(
            '%s (%s), модуль %s, строка %s: %s',
            Event::getTitle(self::$eventClass), $errno, $errfile, $errline, $errstr
        );
    }

    private static function registerEvent($message){
        $event = new Event(self::$eventClass, self::class, $message);
    }

    public static function dispatch($code, $message){
        if(!headers_sent()){
            (new MessageController())->error($code, ['title' => Event::getTitle(self::$eventClass), 'message' => $message]);
        }
    }

}
