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

    protected function getDefaultConfig(): array
    {
        return Config::get('services.LDAP') ?? [];
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

        return true;
    }

    public function bindUser(string $login, string $password, string $domain = 'pnhz.kz'): bool
    {
        // Не трогаем основное соединение!
        $fullUser = strpos($login, '@') !== false ? $login : $login . '@' . $domain;
        $conn = ldap_connect($this->config['ldap_host'] . ':' . ($this->config['ldap_port'] ?? 389));
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        $result = @ldap_bind($conn, $fullUser, $password);

        ldap_unbind($conn);

        return $result;
    }

    // Привязка сервисной учётки для поиска (на основном соединении)
    public function bindService(): bool
    {
        $user = $this->config['ldap_username'] ?? '';
        $pass = $this->config['ldap_password'] ?? '';
        $domain = $this->config['ldap_domain'] ?? 'pnhz.kz';
        $fullUser = strpos($user, '@') !== false ? $user : $user . '@' . $domain;

        $this->bind = @ldap_bind($this->connection, $fullUser, $pass);

        return $this->bind;
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
        return $this->bindService() ? $this->search($resource, $params) : [];
    }

    public function getConnection(): mixed
    {
        return $this->connection;
    }

    public function search(string $filter, array $attributes = ['*', '+']): array
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        $this->bindService();

        $searchResult = ldap_search($this->connection, $this->config['ldap_dn'], $filter, $attributes);

        if (!$searchResult) {
            return [];
        }

        $entries = ldap_get_entries($this->connection, $searchResult);
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

    public function userExists(string $login): bool{
        return !empty($this->search("(samaccountname={$login})")[0]);
    }

    public function findUser(string $login)
    {
        $result = $this->search("(samaccountname={$login})");
        return !empty($result[0]) ? $result[0] : null;
    }

    public function getUserData(string $samaccountname, bool $ext_only = false): array
    {
        if (empty($samaccountname)) {
            return [];
        }

        $filter = "(samaccountname=$samaccountname)";
        $attributes = [
            'samaccountname',
            'mail',
            'cn',
            'displayname',
            'company',
            'title',
            'distinguishedname',
            'extensionattribute1',
            'extensionattribute6',
            'extensionattribute7',
            'extensionattribute8',
            'extensionattribute9',
            'extensionattribute10',
            'extensionattribute11',
            'extensionattribute12'
        ];

        $data = $this->search($filter, ($ext_only ? $attributes : ['*', '+']));
        return $data[0] ?? [];
    }

    public function checkUserPassword(string $login, string $password, string $domain = 'pnhz.kz'): bool
    {
        // Не трогаем основной bind, отдельный bind для проверки
        return $this->bindUser($login, $password, $domain);
    }

    public function extractDivisionFromDn(string $dn): string{
        $codeOuRegex = '/^\d{4,}(?:\s+|\s*[-–—]\s*)/u';

        $parts = preg_split('/(?<!\\\\),\s*/u', $dn) ?: [];
        $ous = [];

        foreach ($parts as $p) {
            if (strpos($p, 'OU=') === 0) {
                $val = substr($p,3);

                $val = strtr($val, [
                    '\\,' => ',', '\\+' => '+', '\\"' => '"', '\\\\' => '\\',
                    '\\#' => '#', '\\;' => ';', '\\=' => '=', '\\<' => '<', '\\>' => '>',
                    '\\ ' => ' ',
                ]);

                $ous[] = trim($val);
            }
        }
        if (!$ous) return '';

        foreach ($ous as $ou) {
            if (preg_match('/^\d{4,}(?:\s+|\s*[-–—]\s*)/u', $ou) === 1) {
                return $ou;
            }
        }

        $nonLatin = array_values(array_filter($ous, static function (string $ou) {
            return preg_match('/\p{Cyrillic}/u', $ou) === 1;
        }));

        if ($nonLatin) return $nonLatin[0];
        return $ous[0];
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
            $this->bindService();
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
