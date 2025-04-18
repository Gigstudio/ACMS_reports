<?php
namespace GigReportServer\System\Clients;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;

class LDAPClient
{    
    protected $connection;
    protected $bind;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $conn_string = $config['ldap_host'] . ':' . $config['ldap_port'];
        $this->connection = ldap_connect($conn_string);

        if (!$this->connection) {
            throw new GeneralException("Не удалось подключиться к LDAP-серверу", 500, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
    }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $this->bind = ldap_bind($this->connection, $config['ldap_username'] . '@pnhz.kz', $config['ldap_password']);

        if (!$this->bind) {
            throw new GeneralException("Ошибка привязки к LDAP: неверные учетные данные", 401, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
        }
    }

    public function search($filter, array $attributes = [])
    {
        $search = ldap_search($this->connection, $this->config['ldap_dn'], $filter, $attributes);

        if (!$search) {
            return [];
        }

        $entries = ldap_get_entries($this->connection, $search);
        $result = [];

        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $result[] = $this->normalizeLdapEntry($entry);
            }
        }

        return $result;
    }

    public function getUserData($samaccountname): array{
        if (empty($samaccountname)) return [];

        $filter = "(samaccountname=$samaccountname)";
        $data = $this->search($filter);
        return $data;
    }

    protected function normalizeLdapEntry(array $entry): array{
        $normalized = [];
        foreach ($entry as $key => $value) {
            if (is_string($key) && isset($value[0])) {
                $normalized[$key] = $value[0];
            }
        }
        return $normalized;
    }

    public function close()
    {
        if ($this->connection) {
            ldap_unbind($this->connection);
        }
    }
}