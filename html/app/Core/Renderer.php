<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Exceptions\GeneralException;

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
        $insert = function(string $name, array $params = []) use ($children): void {
            if (isset($children[$name])) {
                $block = $children[$name];
                $merged = array_merge($block->getData(), $params);
                $blockWithParams = Block::make($block->getTemplate(), $merged)
                    ->with($block->getChildren());
                echo $this->render($blockWithParams);
            }
        };

        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new GeneralException("Ошибка при рендере шаблона", 500, [
                'detail' => $e->getMessage()
            ]);
        }

        return ob_get_clean();
    }
}
