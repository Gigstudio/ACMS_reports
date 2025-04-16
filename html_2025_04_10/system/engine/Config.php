<?php
namespace GigReportServer\System\Engine;
defined('_RUNKEY') or die;

class Config
{
    protected static array $config = [];

    public function __construct(){
        $this->loadConfig();
    }

    private function loadConfig(): void {
        $initConfigFile = 'initconf.json';

        $jsonConfig = $this->readJSONFile(PATH_CONFIG . $initConfigFile);
        self::$config = is_array($jsonConfig) ? $jsonConfig : [];

        // Подгружаем пользовательские настройки из БД
        // if (class_exists('GigReportServer\\System\\Engine\\Database')) {
        //     $db = new Database();
        //     $userConfig = $db->fetchAssoc("SELECT `key`, `value` FROM settings");
        //     foreach ($userConfig as $row) {
        //         $keys = explode('.', $row['key']);
        //         $this->setNestedConfig(self::$config, $keys, json_decode($row['value'], true) ?? $row['value']);
        //     }
        // }
    }

    private function readJSONFile($filename): array {
        if (!file_exists($filename)) {
            throw new \RuntimeException("Config Error: File not found - $filename");
        }

        $data = file_get_contents($filename);
        $decoded = json_decode($data, true);

        // $decodedData = json_decode($decoded, true);
        if (!is_array($decoded)){
            throw new \RuntimeException("Config Error: JSON decoding failed for file $filename");
        }
   
        return $decoded;
    }

    protected function setNestedConfig(array &$config, array $keys, $value) {
        $key = array_shift($keys);
        
        if (empty($keys)) {
            $config[$key] = $value;
        } else {
            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }
            $this->setNestedConfig($config[$key], $keys, $value);
        }
    }

    public static function get(string $key = null, mixed $default = null): mixed {
        if (empty(self::$config)) {
            (new self())->loadConfig();
        }

        if ($key === null) {
            return self::$config;
        }

        if (is_string($key)){
            return self::$config[$key];
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        // var_dump($value);

        return $value;
    }

    public static function set(string $key, mixed $value): void {
        $keys = explode('.', $key);
        $configRef = &self::$config;

        foreach ($keys as $k) {
            if (!isset($configRef[$k]) || !is_array($configRef[$k])) {
                $configRef[$k] = [];
            }
            $configRef = &$configRef[$k];
        }

        $configRef = $value;
    }
}