<?php
namespace GigReportServer\System\Services;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Application;
use GigReportServer\System\Engine\Database;

class UserRepository
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    public function findByLogin(string $login): ?array
    {
        return $this->db->first('users', ['login' => $login]);
    }

    public function createFromLdap(array $ldap): array
    {
        $this->db->insert('users', [
            'login' => strtolower($ldap['samaccountname'] ?? ''),
            'email' => $ldap['mail'] ?? null,
            'full_name' => $ldap['displayname'] ?? null,
            'id_from_1c' => $ldap['extensionattribute10'] ?? null,
            'is_imported' => 1,
            'is_active' => 1,
        ]);

        return $this->findByLogin(strtolower($ldap['samaccountname']));
    }
}
