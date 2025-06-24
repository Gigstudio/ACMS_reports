<?php
namespace GIG\Presentation\View;

defined('_RUNKEY') or die;

class StatusbarBuilder
{
    protected array $indicators;

    public function __construct(array $indicators)
    {
        $this->indicators = $indicators;
    }

    public function render()
    {
        $items = array_keys($this->indicators) ?? [];
        if (count($items) < 1) return '';
        
        $renderItem = function ($item) use (&$renderItem) {
            $html = "<span class='statusbar-light'></span><span class='statusbar-name'>$item</span>";
            return "<span class='statusbar-service' data-service='$item'>$html</span>";
        };
        $itemsHtml = array_filter(array_map($renderItem, $items));

        return empty($itemsHtml) ? '' : 
            "<div class='statusbar'>\n
                <span class='statusbar-label'>&copy; ".date('Y')." </span>\n
                <span class='statusbar-divider'></span>\n
                " . implode("\n", $itemsHtml) . "\n</div>";
    }
}
