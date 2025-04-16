<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

class MenuBuilder {
    protected array $menuItems;

    public function __construct(array $menuItems) {
        $this->menuItems = $menuItems;
    }

    public function render(string $menuName): string {
        if (!isset($this->menuItems[$menuName])) return '';

        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return implode("\n", array_map(
            function ($item) use ($currentUrl) {
                $isActive = ($currentUrl === parse_url($item['link'], PHP_URL_PATH)) ? ' active' : '';
                return sprintf(
                    "<a title='%s' class='menu-item%s' href='%s'><span class='fadable1000'>%s</span>%s</a>",
                    htmlspecialchars($item['title'], ENT_QUOTES),
                    $isActive,
                    htmlspecialchars($item['link'], ENT_QUOTES),
                    htmlspecialchars($item['title'], ENT_QUOTES),
                    !empty($item['icon']) ? "<i class='" . htmlspecialchars($item['icon'], ENT_QUOTES) . "'></i>" : ''
                );
            },
            $this->menuItems[$menuName]
        )) . "\n";
    }
}