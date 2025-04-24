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
        $passwordHash = password_hash(Application::getInstance()->getRequest()->getPostParam('pass') ?? '', PASSWORD_BCRYPT);

        $this->db->insert('users', [
            'login' => strtolower($ldap['samaccountname'] ?? ''),
            'password' => $passwordHash,
            'email' => $ldap['mail'] ?? null,
            'full_name' => $ldap['displayname'] ?? null,
            'first_name' => $ldap['givenname'] ?? null,
            'last_name' => $ldap['sn'] ?? null,
            'id_from_1c' => $ldap['extensionattribute10'] ?? null,
            'division_id' => $perco[''] ?? null,
            'position_id' => $perco[''] ?? null,
            'bage_number' => $perco['extensionattribute10'] ?? null,
            'source' => 'ldap',
            'is_active' => 1,
        ]);

        return $this->findByLogin(strtolower($ldap['samaccountname']));
    }
}
