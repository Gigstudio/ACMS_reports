<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Http\Request;
use GigReportServer\System\Http\Response;

class Application
{
    private Config $config;
    public static Application $app;
    public LDAPClient $ldapClient;
    // public PercoClient $percoClient;
    // public MySQLClient $mysqlClient;
    public Request $request;
    public Response $response;
    private Router $router;

    public function __construct($config){
        self::$app = $this;
        $this->config = $config;
        $this->ldapClient = new LDAPClient($config->get('ldap'));
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request);

        $this->setup();
    }

    private function setup(){
        $styles = $this->config->get('common.css');
        $scrypts = $this->config->get('common.js');
        foreach($styles as $css){
            AssetManager::addStyle("/assets/css/$css.css");
        }
        foreach($scrypts as $scrypt){
            AssetManager::addScript("/assets/js/$scrypt.js");
        }
    }

    public static function getInstance(): self{
        return self::$app ?? new self(new Config);
    }

    public function getConfig($key, $default = null){
        return $this->config::get($key, $default);
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