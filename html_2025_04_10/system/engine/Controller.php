<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Http\Response;
use GigReportServer\System\Engine\TemplateEngine;

class Controller
{
    protected Response $response;
    protected Application $app;
    protected array $config = [];
    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->config = $this->app->getConfig()::get() ?? Config::get();
        $this->response = $this->app->getResponse() ?: Application::getInstance()->getResponse();
    }

    protected function render($view, $data, $query): void{
        $data['content'] = $view;
        $data['query'] = $query;
        $data['app'] =  $this->config['app'];
        $data['common'] =  $this->config['common'];
        $data['mainmenu'] = $data['mainmenu'] ?? [
            ['title' => 'Главная', 'link' => '/', 'icon' => ''],
            ['title' => 'Тестирование API', 'link' => '/api_test', 'icon' => ''],
            ['title' => 'Отчеты', 'link' => '/reports', 'icon' => ''],
        ];
        $data['sidemenu'] = $data['sidemenu'] ?? [];
        $data['bottommenu'] = $data['bottommenu'] ?? [
            ['title' => 'О проекте', 'link' => '/about', 'icon' => ''],
            ['title' => 'Контакты', 'link' => '/contact'],
        ];
        if ($this->config['debug_error']['mode'] === 'debug'){
            $data['inject'] = Console::createConsole();
            $data['common']['js'][] = 'console';
        }

        TemplateEngine::setLayout($data['layout'] ?? 'default');
        $output = TemplateEngine::render($data);

        preg_match('/<head>(.*?)<\/head>/is', $output, $headMatch);
        preg_match('/<body>(.*?)<\/body>/is', $output, $bodyMatch);

        $head = $headMatch[1] ?? '';
        $body = $bodyMatch[1] ?? '';

        $this->response->html($head, $body, $data['error_code'] ?? 200);
    }
}
