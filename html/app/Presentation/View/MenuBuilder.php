<?php
namespace GIG\Presentation\View;

defined('_RUNKEY') or die;

class MenuBuilder {
    protected array $menuItems;
    protected array $userRoles;

    public function __construct(array $menuItems, array $userRoles = []) {
        $this->menuItems = $menuItems;
        $this->userRoles = $userRoles;
    }

    public function render(string $menuName): string {
        if (!isset($this->menuItems[$menuName])) return '';

        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $renderItem = function ($item) use (&$renderItem, $currentUrl): string {
            if (!empty($item['roles']) && is_array($item['roles'])) {
                if (empty(array_intersect($this->userRoles, $item['roles']))) {
                    return null;
                }
            }

            $isActive = ($currentUrl === parse_url($item['link'] ?? '#', PHP_URL_PATH)) ? ' active' : '';
            $attributes = [
                'class' => 'menu-item' . $isActive,
                'href' => htmlspecialchars($item['link'] ?? '#', ENT_QUOTES),
                'title' => htmlspecialchars($item['title'] ?? '', ENT_QUOTES),
            ];
            if (!empty($item['target'])) $attributes['target'] = htmlspecialchars($item['target'], ENT_QUOTES);
            if (!empty($item['rel'])) $attributes['rel'] = htmlspecialchars($item['rel'], ENT_QUOTES);
            if (!empty($item['disabled'])) $attributes['class'] .= ' disabled';

            $attrString = implode(' ', array_map(
                fn($k, $v) => "$k='$v'",
                array_keys($attributes),
                $attributes
            ));

            $icon = !empty($item['icon']) ? "<i class='" . htmlspecialchars($item['icon'], ENT_QUOTES) . "'></i>" : '';
            $label = htmlspecialchars($item['title'] ?? '', ENT_QUOTES);

            $html = "<a $attrString><span class='fadable1000'>$label</span>$icon</a>";

            if (!empty($item['children']) && is_array($item['children'])) {
                $childrenHtml = array_filter(array_map($renderItem, $item['children']));
                if (!empty($childrenHtml)) {
                    $html .= "\n<ul class='submenu'>\n" . implode("\n", $childrenHtml) . "\n</ul>";
                }
            }

            return "<li>$html</li>";
        };
        $itemsHtml = array_filter(array_map($renderItem, $this->menuItems[$menuName]));

        return empty($itemsHtml) ? '' : "<ul class='menu-level'>\n" . implode("\n", $itemsHtml) . "\n</ul>";
    }
}