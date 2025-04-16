<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Http\Request;
use GigReportServer\System\Http\Response;

class Application
{
    public static Application $app;
    private Config $config;
    public Request $request;
    public Response $response;
    private Router $router;

    public function __construct()
    {
        self::$app = $this;
        $this->config = new Config();
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request);
    }

    public static function getInstance(): self
    {
        return self::$app ?? new self();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function run(): void
    {
        try {
            $this->router->loadRoutes();
            $this->router->dispatch();
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }
}
