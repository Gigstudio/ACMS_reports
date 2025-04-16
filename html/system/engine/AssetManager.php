<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

class AssetManager {
    protected static array $styles = [];
    protected static array $scripts = [];

    public static function addStyle(string $href): void {
        self::$styles[] = $href;
    }

    public static function addScript(string $src): void {
        self::$scripts[] = $src;
    }

    public static function getStyles(): array {
        return self::$styles;
    }

    public static function getScripts(): array {
        return self::$scripts;
    }

    public static function reset(): void {
        self::$styles = [];
        self::$scripts = [];
    }
}