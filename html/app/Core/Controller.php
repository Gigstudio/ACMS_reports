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

    protected function json(array $payload, int $status = 200): void {
        if (!$this->isApiRequest()) {
            throw new GeneralException(
                "Метод json() может вызываться только в контексте API-запроса",
                500,
                [
                    'detail' => "Попытка возврата JSON вне API. Проверьте логику вызова контроллера."
                ]
            );
        }

        $payload['status'] ??= $status >= 400 ? 'error' : 'success';
        $payload['code'] ??= $status;
        $this->response->setStatus($status);
        $this->response->json($payload);
    }

    protected function render(Block $block): void {
        // if($this->app->getConfig('settings.debug_mode', 'deploy') === 'debug'){
        if(DEV_MODE){
            $console = Block::make('partials/console');
            $block = $block->with(['console' => $console]);
        }
        $this->response->html($this->renderer->render($block), $this->statusCode ?? 200);
    }
}
