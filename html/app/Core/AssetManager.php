<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class AssetManager
{
    protected static array $styles = [];
    protected static array $scripts = [];

    public static function addStyle(string $href): void
    {
        if (!in_array($href, self::$styles)) {
            self::$styles[] = $href;
        }
    }

    public static function addScript(string $src): void
    {
        if (!in_array($src, self::$scripts)) {
            self::$scripts[] = $src;
        }
    }

    public static function getStyles(): array
    {
        return self::$styles;
    }

    public static function getScripts(): array
    {
        return self::$scripts;
    }

    // public static function renderStyles(): string
    // {
    //     return implode("\n", array_map(
    //         fn($href) => "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($href) . "\">",
    //         self::$styles
    //     ));
    // }

    // public static function renderScripts(): string
    // {
    //     return implode("\n", array_map(
    //         fn($src) => "<script src=\"" . htmlspecialchars($src) . "\"></script>",
    //         self::$scripts
    //     ));
    // }

    // public static function reset(): void
    // {
    //     self::$styles = [];
    //     self::$scripts = [];
    // }
}
