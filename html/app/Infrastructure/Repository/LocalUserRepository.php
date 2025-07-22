<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\PasswordManager;
use GIG\Domain\Entities\User;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Persistence\MySQLClient;

class LocalUserRepository
{
    protected MySQLClient $db;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->columns = $this->getColumnNames('user');
    }

    private function getColumnNames(string $table): array
    {
        $result = $this->db->describeTable($table);
        return array_column($result, 'Field');
    }

    public function findById(int $id): ?User
    {
        $data = $this->db->first('user', ['id' => $id]);
        return $data ? new User($data) : null;
    }

    public function findByLogin(string $login): ?User
    {
        $data = $this->db->first('user', ['login' => $login]);
        return $data ? new User($data) : null;
    }

    public function search(string $field, string $value): array
    {
        if (!in_array($field, $this->columns, true)) {
            throw new GeneralException(
                "Поле '$field' отсутствует в таблице user.",
                400,
                [
                    'reason' => 'missing_field',
                    'detail' => "Поле '$field' отсутствует в таблице user. Поиск невозможен."
                ]
            );
        }
        $rows = $this->db->get('user', [$field => $value]);
        $result = [];
        foreach ($rows as $row) {
            $result[] = User::fromArray($row);
        }
        return $result;
    }
    
    public function create(User $user): void
    {
        $data = $user->toArray([
            'login', 'password', 'email', 'table_number', 'perco_id', 'card_number', 'role_id', 'is_active', 'is_blocked', 'source'
        ]);
        $this->db->insert('user', $data);
    }

    public function update(User $user): void
    {
        $data = $user->toArray([
            'email', 'table_number', 'perco_id', 'card_number', 'role_id', 
            'is_active', 'is_blocked', 'source', 'last_login_at'
        ]);
        $this->db->update('user', $data, ['login' => $user->login]);
    }

    public function updatePassword(string $login, string $newHash): void
    {
        $this->db->update('user', ['password' => $newHash], ['login' => $login]);
    }

    public function block(User $user): void
    {
        $this->db->update('user', ['is_blocked' => 1], ['login' => $user->login]);
    }

    public function createFromLdap(array $ldap, string $password): ?User
    {
        file_put_contents(PATH_LOGS.'user_management.log', var_export($ldap, true), FILE_APPEND );
        // throw new GeneralException('CREATING USER FROM LDAP', 200, [
        //     'detail' => json_encode($ldap)
        // ]);
        $login = strtolower($ldap['samaccountname'] ?? '');

        // ----------- COMPANY -----------
        $companyId = null;
        if (!empty($ldap['company'])) {
            $companyId = $this->resolveDictionaryEntry('company', $ldap['company']);
        }

        // ----------- DIVISION -----------
        $divisionId = null;
        $divisionName = null;
        if (isset($ldap['distinguishedname'])) {
            preg_match_all('/OU=([^,]+)/u', $ldap['distinguishedname'], $ouMatches);
            if (!empty($ouMatches[1])) {
                $divisionName = trim($ouMatches[1][0]);
                $divisionId = $this->resolveDictionaryEntry('division', $divisionName);
            }
        }
// 'login', 'password', 'email', 'table_number', 'perco_id', 'card_number', 'role_id', 'is_active', 'is_blocked', 'source'
        $user = new User([
            'login'         => $login,
            'password'      => PasswordManager::hash($password),
            'email'         => $ldap['mail'] ?? null,
            'table_number'  => '',
            'perco_id'      => '',
            'card_number'   => '',
            'role_id'   => '',
            'is_active'     => 1,
            'is_blocked'    => 0,
            'source'        => 'ldap'
        ]);

        // ENRICH — заполняем остальные поля из perco
        $user = $this->enrichUserWithPerco($user, $divisionName);
        $user->is_fulfilled = (int)(!empty($user->badge_number));

        $this->save($user);
        return $this->findByLogin($login);
    }

}