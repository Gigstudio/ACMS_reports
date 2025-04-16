<?php
namespace GigReportServer\API;

defined('_RUNKEY') or die;

class LDAPClient {
    private $connection;
    private $ldapHost;
    private $ldapPort;

    public function __construct($host, $port = 389) {
        $this->ldapHost = $host;
        $this->ldapPort = $port;
    }

    public function connect() {
        $this->connection = ldap_connect($this->ldapHost);
        if (!$this->connection) {
            throw new \Exception("Не удалось подключиться к LDAP-серверу");
        }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
    }

    public function authenticate($username, $password): bool {
        if (!$this->connection) {
            $this->connect();
        }

        $bind = @ldap_bind($this->connection, $username, $password);

        if (!$bind) {
            throw new \Exception("Ошибка аутентификации: " . ldap_error($this->connection));
        }

        return true;
    }

    public function __destruct() {
        if ($this->connection) {
            ldap_unbind($this->connection);
        }
    }
}
