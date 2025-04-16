<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

class FontManager {
    protected static array $fonts = [];

    public static function register(string $name, string $url): void {
        self::$fonts[$name] = $url;
    }

    public static function getFontLinks(): array {
        return array_values(self::$fonts);
    }
}
