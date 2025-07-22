<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\Config;
use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Infrastructure\Persistence\FirebirdClient;
use GIG\Infrastructure\Persistence\LdapClient;
use GIG\Infrastructure\Persistence\PercoWebClient;
use GIG\Core\Request;
use GIG\Core\Response;
use GIG\Core\Router;
use GIG\Domain\Entities\User;

class Application
{
    protected Config $config;
    protected static ?Application $app = null;

    protected ?DatabaseClientInterface $db = null;
    protected ?DatabaseClientInterface $firebird = null;
    protected ?LdapClient $ldapClient = null;
    protected ?PercoWebClient $percoWebClient = null;
    protected ?User $currentUser = null;

    public Request $request;
    public Response $response;
    public Router $router;

    public function __construct(?Config $config = null)
    {
        self::$app = $this;
        $this->config = $config ?? new Config();

        $this->request  = new Request();
        $this->response = new Response();
        $this->router   = new Router($this->request);

        $this->setupAssets();
    }

    /**
     * Статический доступ к глобальному экземпляру приложения.
     */
    public static function getInstance(): self
    {
        if (!self::hasInstance()) {
            self::$app = new self(new Config());
        }
        return self::$app;
    }

    public static function hasInstance(): bool
    {
        return isset(self::$app);
    }

    public function getConfig(string $key = null, $default = null): mixed
    {
        return $this->config::get($key, $default);
    }

    public function setConfig(string $key, $value): void
    {
        $this->config::set($key, $value);
    }

    public function getMysqlClient(): ?DatabaseClientInterface
    {
        if (!$this->db) {
            $this->db = new MySQLClient($this->config->get('services.MySQL'));
        }
        return $this->db;
    }

    public function getFirebirdClient(): ?DatabaseClientInterface
    {
        if (!$this->firebird) {
            $this->firebird = new FirebirdClient($this->config->get('services.PERCo-S20'));
        }
        return $this->firebird;
    }

    public function getLdapClient(): ?LdapClient
    {
        if (!$this->ldapClient) {
            $this->ldapClient = new LdapClient($this->config->get('services.LDAP'));
        }
        return $this->ldapClient;
    }

    public function getPercoWebClient(): ?PercoWebClient
    {
        if (!$this->percoWebClient) {
            $this->percoWebClient = new PercoWebClient($this->config->get('services.PERCo-Web'));
        }
        return $this->percoWebClient;
    }

    public function getServiceByName(string $name)
    {
        $services = $this->getConfig('services') ?? [];
        if(!array_key_exists($name, $services)){
            return null;
        }
        $suffix = $services[$name]['client'];
        $method = "get$suffix";
        if (!method_exists($this, $method)){
            return null;
        }
        return $this->{$method}();
    }

    public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }
    
    public function setCurrentUser(?User $user): void
    {
        $this->currentUser = $user;
    }

    protected function setupAssets(): void
    {
        $styles = $this->config->get('styles', []);
        $scripts = $this->config->get('scripts.js', []);
        foreach ($styles as $css) {
            AssetManager::addStyle("/assets/css/$css.css");
        }
        foreach ($scripts as $js) {
            AssetManager::addScript("/assets/js/$js.js");
        }
    }

    /**
     * Запуск основного цикла приложения: роутинг и dispatch
     */
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
