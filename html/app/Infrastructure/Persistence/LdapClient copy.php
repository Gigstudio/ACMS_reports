<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use GIG\Core\Config;
use GIG\Domain\Exceptions\GeneralException;

class LdapClient
{
    protected $connection;
    protected $bind;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: $this->getDefaultConfig();
        $this->connect();
    }

    public function connect(): bool
    {
        $conn_string = $this->config['ldap_host'] . ':' . ($this->config['ldap_port'] ?? 389);
        $this->connection = ldap_connect($conn_string);

        if (!$this->connection) {
            throw new GeneralException("Не удалось подключиться к LDAP-серверу", 500, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
        }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $user = $this->config['ldap_username'] ?? '';
        $pass = $this->config['ldap_password'] ?? '';
        $domain = $this->config['ldap_domain'] ?? 'pnhz.kz';

        $fullUser = strpos($user, '@') !== false ? $user : $user . '@' . $domain;

        $this->bind = @ldap_bind(
            $this->connection,
            $fullUser,
            $pass
        );

        if (!$this->bind) {
            throw new GeneralException("Ошибка привязки к LDAP: неверные учетные данные", 401, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
        }

        return true;
    }

    protected function getDefaultConfig(): array
    {
        return Config::get('services.LDAP') ?? [];
    }

    public function isConnected(): bool
    {
        return !empty($this->connection);
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            ldap_unbind($this->connection);
            $this->connection = null;
            $this->bind = false;
        }
    }

    public function request(string $resource, array $params = []): array
    {
        // $resource - например фильтр поиска
        // $params - атрибуты поиска
        return $this->search($resource, $params);
    }

    public function getConnection(): mixed
    {
        return $this->connection;
    }

    public function search(string $filter, array $attributes = ['*', '+']): array
    {
        if (!$this->isConnected()) {
            throw new GeneralException("LDAP не подключён", 401, [
                'detail' => "Проблемы с подключением к LDAP.",
            ]);
        }

        $search = ldap_search($this->connection, $this->config['ldap_dn'], $filter, $attributes);

        if (!$search) {
            return [];
        }
    
        $entries = ldap_get_entries($this->connection, $search);
        $result = [];
    
        if (isset($entries['count']) && $entries['count'] > 0) {
            for ($i = 0; $i < $entries['count']; $i++) {
                $normalized = $this->normalizeLdapEntry($entries[$i]);
                if (!empty($normalized)) {
                    $result[] = $normalized;
                }
            }
        }
        return $result;
    }

    public function getUserData(string $samaccountname): array
    {
        if (empty($samaccountname)) {
            return [];
        }

        $filter = "(samaccountname=$samaccountname)";
        $data = $this->search($filter);
        return $data[0] ?? [];
    }

    protected function normalizeLdapEntry(array $entry): array
    {
        $normalized = [];
        foreach ($entry as $key => $value) {
            if (is_string($key) && isset($value[0]) && $key !== 'count') {
                $normalized[$key] = $value[0];
            }
        }
        return $normalized;
    }

    public function checkStatus(): array
    {
        try {
            if (!$this->isConnected()) {
                $this->connect();
            }
            // Простой запрос
            $base_dn = $this->config['ldap_dn'] ?? '';
            $search = @ldap_read($this->connection, $base_dn, '(objectClass=*)', ['dn']);
            if (!$search) {
                return [
                    'status' => 'fail',
                    'message' => "LDAP: не удалось выполнить тестовый запрос",
                ];
            }
            return [
                'status' => 'ok',
                'message' => "LDAP подключен",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'fail',
                'message' => "LDAP: " . $e->getMessage(),
                'details' => method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null
            ];
        }
    }
}
