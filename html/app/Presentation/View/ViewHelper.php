<?php
namespace GIG\Presentation\View;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\AssetManager;

class ViewHelper
{
    public static function config(string $key, $default = null): string
    {
        $value = Application::getInstance()->getConfig($key, $default);
        return htmlspecialchars((string) $value);
    }

    public static function fonts(): string
    {
        return implode("\n", array_map(
            fn($f) => '<link rel="stylesheet" href="' . htmlspecialchars($f) . '">',
            FontManager::getFontLinks()
        ));
    }

    public static function styles(): string
    {
        return implode("\n", array_map(
            fn($s) => '<link rel="stylesheet" href="' . htmlspecialchars($s) . '">',
            AssetManager::getStyles()
        ));
    }

    public static function scripts(): string
    {
        return implode("\n", array_map(
            fn($s) => '<script type="module" src="' . htmlspecialchars($s) . '"></script>',
            AssetManager::getScripts()
        ));
    }

    public static function menu(string $name): string
    {
        $file = PATH_CONFIG . 'menuitems.php';
        if (!file_exists($file)) {
            return '';
        }
        $menus = require $file;
        $builder = new MenuBuilder($menus);
        return $builder->render($name);
    }
}
