<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\Config;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Infrastructure\Persistence\FirebirdClient;
// use GIG\Infrastructure\Clients\LDAPClient;
// use GIG\Infrastructure\Clients\PercoWebClient;
use GIG\Core\Request;
use GIG\Core\Response;
use GIG\Core\Router;
// use GIG\Domain\Entities\User;

class Application
{
    protected Config $config;
    protected static ?Application $app = null;

    protected ?DatabaseClientInterface $db = null;
    protected ?DatabaseClientInterface $firebird = null;
    // protected ?LDAPClient $ldapClient = null;
    // protected ?PercoWebClient $percoWebClient = null;
    // protected ?User $currentUser = null;

    public Request $request;
    public Response $response;
    public Router $router;

    /**
     * Основной конструктор приложения
     */
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
        if (self::$app === null) {
            self::$app = new self(new Config());
        }
        return self::$app;
    }

    public static function hasInstance(): bool
    {
        return isset(self::$app);
    }

    /**
     * Инициализация ядра приложения: подключение БД, инициализация клиентов.
     */
    public function init(): self
    {
        // Можно инициализировать дополнительные сервисы по мере надобности

        return $this;
    }

    public function getConfig(string $key, $default = null): mixed
    {
        return $this->config::get($key, $default);
    }

    public function setConfig(string $key, $value): void
    {
        $this->config::set($key, $value);
    }

    public function getMysql(): ?DatabaseClientInterface
    {
        if (!$this->db) {
            $this->db = new MySQLClient($this->config->get('database'));
        }
        return $this->db;
    }

    public function getFirebird(): ?DatabaseClientInterface
    {
        if (!$this->firebird) {
            $this->firebird = new FirebirdClient($this->config->get('firebird'));
        }
        return $this->firebird;
    }

    // public function getLdap(): ?LDAPClient
    // {
    //     if (!$this->ldapClient) {
    //         try {
    //             $this->ldapClient = new LDAPClient($this->config->get('ldap'));
    //         } catch (\Throwable $e) {
    //             $this->ldapClient = null;
    //             Event::log(Event::EVENT_WARNING, self::class, "LDAP недоступен: " . $e->getMessage());
    //         }
    //     }
    //     return $this->ldapClient;
    // }

    // public function getPercoWebClient(): ?PercoWebClient
    // {
    //     if (!$this->percoWebClient) {
    //         try {
    //             $this->percoWebClient = new PercoWebClient($this->config->get('perco'));
    //         } catch (\Throwable $e) {
    //             $this->percoWebClient = null;
    //             Event::log(Event::EVENT_WARNING, self::class, "PERCo-Web недоступен: " . $e->getMessage());
    //         }
    //     }
    //     return $this->percoWebClient;
    // }

    // public function getCurrentUser(): ?User
    // {
    //     return $this->currentUser;
    // }
    
    // public function setCurrentUser(?User $user): void
    // {
    //     $this->currentUser = $user;
    // }

    // Можно раскомментировать когда потребуется подключение стилей/скриптов
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
