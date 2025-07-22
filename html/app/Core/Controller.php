<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Exceptions\GeneralException;

abstract class Controller
{
    protected Application $app;
    protected Request $request;
    protected Response $response;
    protected Renderer $renderer;
    public int $statusCode = 200;
    
    public function __construct(){
        $this->app = Application::getInstance();
        $this->request = $this->app->request;
        $this->response = $this->app->response;
        $this->renderer = new Renderer();
    }

    protected function setStatus(int $statusCode): void {
        $this->statusCode = $statusCode;
    }

    protected function isApiRequest(): bool {
        return $this->request->isApi();
    }

    protected function render(Block $block): void {
        if(DEV_MODE){
            $console = Block::make('partials/console');
            $block = $block->with(['console' => $console]);
        }
        $this->response->html($this->renderer->render($block), $this->statusCode ?? 200);
    }
}
