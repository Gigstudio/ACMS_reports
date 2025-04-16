<?php
namespace GigReportServer\System\Engine;
defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;

class Config
{
    protected static array $config = [];
    private static string $init = PATH_CONFIG . 'init.json';

    public function __construct(){
        $this->loadConfig();
    }

    private function loadConfig(){
        $jsonConfig = $this->readJSONFile(self::$init);
        self::$config = is_array($jsonConfig) ? $jsonConfig : [];
    }

    private function readJSONFile($filename): array {
        if (!file_exists($filename)) {
            throw new GeneralException("Ошибка загрузки конфигурации", 500, [
                'detail' => "Файл: $filename не найден.",
            ]);
        }

        $data = file_get_contents($filename);
        $decoded = json_decode($data, true);

        if (!is_array($decoded)){
            throw new GeneralException("Ошибка загрузки конфигурации", 500, [
                'detail' => "Файл: $filename не содержит корректный JSON.",
            ]);
        }
        return $decoded;
    }

    public static function get($key = null, $default = null){
        if (empty(self::$config)) {
            (new self())->loadConfig();
        }

        if ($key === null) {
            return self::$config;
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
}