<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

class Event
{
    public static const EVENT_INFO = 0;
    public static const EVENT_MESSAGE = 1;
    public static const EVENT_WARNING = 2;
    public static const EVENT_ERROR = 3;
    public static const EVENT_FATAL = 4;
    private string $time;
    private int $class;
    private string $source;
    private string $message;
    public static string $logPath = PATH_STORAGE . 'events.log';
    private static array $eventTypes;

    public function __construct(int $class, string $source, string $message)
    {
        $this->time = date('[Y-m-d H:i:s]');
        $this->class = $class;
        $this->source = $source;
        $this->message = $message;
        self::$eventTypes = self::getFromConfig();

        $this->log();
    }

    private static function getFromConfig(){
        return Application::getInstance()->getConfig()::get('events');
    }

    public function getText(): string{
        return "{$this->source}::{$this->time} {$this->message}";
    }

    public static function getTitle($class){
        return empty(self::$eventTypes) ? self::getFromConfig()[$class]['title'] : self::$eventTypes[$class]['title'];
    }

    public static function getClass($class){
        return empty(self::$eventTypes) ? self::getFromConfig()[$class]['class'] : self::$eventTypes[$class]['class'];
    }

    private function log(): void
    {
        $logText = $this->getText();
        $logData = [
            'level' => $this->class,
            'class' => self::getClass($this->class),
            'source' => $this->source,
            'time' => $this->time,
            'message' => $this->message
        ];
        Console::setMessage($logData);
        $this->toFile($logText);
        // $this->toDB($m_data);
    }

    private function toFile(string $text){
        file_put_contents(self::$logPath, $text . PHP_EOL, FILE_APPEND);
    }
}