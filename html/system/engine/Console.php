<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

class Console
{
    protected static array $messages = [];

    public static function addMessage($type, $source, $message){
        $event = new Event($type, $source, $message);
        return ($event instanceof Event);
    }

    public static function setMessage($data){
        self::$messages = $_SESSION['console_messages'] ?? [];
        self::$messages[] = $data;
        $_SESSION['console_messages'] = self::$messages;
    }

    public static function getMessages($filter){
        self::$messages = $_SESSION['console_messages'] ?? [];
        $filterLevel = (int) $filter;
        $filtered = array_filter(self::$messages, function($msg) use ($filterLevel) {
            return isset($msg['level']) && (int)$msg['level'] >= $filterLevel;
        });
        return array_values($filtered);
    }

    public static function clearMessages(){
        self::$messages = [];
        unset($_SESSION['console_messages']);
    }

    public static function createConsole(){
        $data = [];
        $console_html_file = PATH_LAYOUTS . 'console.php';
        if(file_exists($console_html_file)){
            $console_html = (file_get_contents(PATH_LAYOUTS . 'console.php'));
            $data = "<script>let inject = " . json_encode($console_html, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";</script>";
        }
        return $data;
    }
}
