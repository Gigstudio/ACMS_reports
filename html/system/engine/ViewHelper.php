<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;

class ViewHelper {
    public static function config(string $key, $default = null): string {
        $value = Application::$app->getConfig($key, $default);
        return htmlspecialchars((string) $value);
    }
    public static function fonts(): string {
        return implode("\n", array_map(
            fn($f) => '<link rel="stylesheet" href="' . htmlspecialchars($f) . '">',
            FontManager::getFontLinks()
        ));
    }

    public static function styles(): string {
        return implode("\n", array_map(
            fn($s) => '<link rel="stylesheet" href="' . htmlspecialchars($s) . '">',
            AssetManager::getStyles()
        ));
    }

    public static function scripts(): string {
        return implode("\n", array_map(
            fn($s) => '<script type="module" src="' . htmlspecialchars($s) . '"></script>',
            AssetManager::getScripts()
        ));
    }

    public static function menu(string $name){
        $file = PATH_CONFIG . 'menuitems.php';
        if(!file_exists($file)){
            // throw new GeneralException("Файл не найден", 500, [
            //     'detail' => "Файл: $file, Проверьте папку config. Убедитесь в наличии файла $file.",
            // ]);
            return '';
        }
        $menus = require $file;
        $builder = new MenuBuilder($menus);
        return $builder->render($name);
    }
}