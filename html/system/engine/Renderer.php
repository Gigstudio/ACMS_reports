<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;

class Renderer {
    public function render(Block $block): string {
        $templateFile = PATH_VIEWS . $block->getTemplate() . '.php';

        if (!file_exists($templateFile)) {
            throw new GeneralException("Файл не найден", 404, [
                'detail' => "Файл шаблона $templateFile не найден.",
            ]);
        }

        foreach ($block->getStyles() as $style) {
            AssetManager::addStyle($style);
        }
        foreach ($block->getScripts() as $script) {
            AssetManager::addScript($script);
        }

        $data = $block->getData();
        $children = $block->getChildren();

        // Делаем доступными в шаблоне
        extract($data);
        ob_start();

        // Утилита рендера вложенных блоков
        $insert = function(string $name) use ($children): void {
            if (isset($children[$name])) {
                echo (new Renderer())->render($children[$name]);
            }
        };

        include $templateFile;

        return ob_get_clean();
    }
}
