<?php
namespace GigReportServer\System\Engine;

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

        $conn_string = $config['ldap_host'].':'.$config['ldap_port'];
        // var_dump('Connection string: '.$conn_string);
        $this->connection = ldap_connect($conn_string);
        // $this->connection = ldap_connect($config['ldap_host'], $config['ldap_port']);

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

        return $entries;
    }

    public function getUserData($samaccountname)
    {
        $filter = "(samaccountname=$samaccountname)";
        $data = $this->search($filter);
        // var_dump($filter);
        // var_dump($data);
        // $data = 
        return $data;
    }

    public function close()
    {
        if ($this->connection) {
            ldap_unbind($this->connection);
        }
    }
}