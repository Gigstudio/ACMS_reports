<?php
namespace GigReportServer\System\Engine;

use GigReportServer\System\Http\Response;

defined('_RUNKEY') or die;

abstract class Controller
{
    protected Application $app;
    protected Response $response;
    protected Renderer $renderer;
    public int $statusCode;
    
    public function __construct(){
        $this->app = Application::getInstance();
        $this->response = $this->app->response;
        $this->renderer = new Renderer();
    }

    protected function setStatus(int $statusCode){
        $this->statusCode = $statusCode;
    }

    protected function render(Block $block): void {
        if($this->app->getConfig('debug_error.mode', 'deploy') === 'debug'){
            $console = Block::make('partials/console');
            $block = $block->with(['console' => $console]);
        }
        $this->response->html($this->renderer->render($block), $this->statusCode ?? 200);
    }
}