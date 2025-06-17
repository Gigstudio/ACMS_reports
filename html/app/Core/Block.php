<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class Block {
    protected string $template;
    protected array $data = [];
    protected array $children = [];
    protected array $styles = [];
    protected array $scripts = [];

    public function __construct(string $template, array $data = []) {
        $this->template = $template;
        $this->data = $data;
    }

    public static function make(string $template, array $data = []): self {
        return new self($template, $data);
    }

    public function with(array $blocks): self {
        foreach ($blocks as $name => $block) {
            if (!$block instanceof self) {
                trigger_error("Block::with(): '$name' не является экземпляром Block. Пропущено.", E_USER_WARNING);
                continue;
            }
            $this->addBlock($name, $block);
        }
        return $this;
    }

    public function addBlock(string $name, Block $block): void {
        $this->children[$name] = $block;
    }

    public function addStyle(string $href){
        if (!in_array($href, $this->styles, true)) {
            $this->styles[] = $href;
        }
        return $this;
    }

    public function addScript(string $src){
        if (!in_array($src, $this->scripts, true)) {
            $this->scripts[] = $src;
        }
        return $this;
    }

    public function getTemplate(): string {
        return $this->template;
    }

    public function getStyles(): array{
        return $this->styles;
    }

    public function getScripts(): array{
        return $this->scripts;
    }

    public function getData(): array {
        return $this->data;
    }

    public function getChild(string $name): ?Block {
        return $this->children[$name] ?? null;
    }

    public function getChildren(): array {
        return $this->children;
    }
}
