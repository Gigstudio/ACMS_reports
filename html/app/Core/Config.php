<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Exceptions\GeneralException;

class Config
{
    protected static array $config = [];
    private static string $init = PATH_CONFIG . 'init.json';

    public function __construct() {
        if (empty(self::$config)) {
            $this->loadConfig();
        }
    }

    private function loadConfig(): void {
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

        if (!is_array($decoded)) {
            throw new GeneralException("Ошибка загрузки конфигурации", 500, [
                'detail' => "Файл: $filename не содержит корректный JSON.",
            ]);
        }
        return $decoded;
    }

    /**
     * Получить значение из конфига по ключу (через точку).
     */
    public static function get($key = null, $default = null) {
        if (empty(self::$config)) {
            (new self())->loadConfig();
        }

        if ($key === null || $key === '') {
            return self::$config;
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    /**
     * Установить значение в конфиге на лету (глубоко по ключу).
     */
    public static function set(string $key, $value): bool {
        if (empty($key)) {
            return false;
        }

        $keys = explode('.', $key);
        $data = &self::$config;

        foreach ($keys as $k) {
            if (!isset($data[$k]) || !is_array($data[$k])) {
                $data[$k] = [];
            }
            $data = &$data[$k];
        }
        $data = $value;
        return true;
    }
}
