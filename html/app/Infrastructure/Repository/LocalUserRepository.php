<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\PasswordManager;
use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Infrastructure\Persistence\LdapClient;
use GIG\Domain\Services\PercoManager;
use GIG\Domain\Services\RoleManager;
use GIG\Domain\Entities\User;
use GIG\Domain\Exceptions\GeneralException;

class LocalUserRepository
{
    protected MySQLClient $db;
    protected LdapClient $ldap;
    protected PercoManager $perco;
    protected RoleManager $roleManager;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->ldap = Application::getInstance()->getLdapClient();
        $this->perco = new PercoManager();
        $this->roleManager = new RoleManager();
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

    public function create(User $user): int
    {
        $this->db->insert('user', $user->toArray($user->getInsertableColumns()));
        return $this->db->lastInsertId();
    }

    public function update(User $user): void
    {
        $data = $user->getUpdatableColumns();
        unset($data['id']);
        $this->db->update('user', $data, ['id' => $user->id]);
    }

    public function updatePassword(string $login, string $newPass): void
    {
        $hash = PasswordManager::hash($newPass);
        $this->db->update('user', ['password' => $hash], ['login' => $login]);
    }

    public function updateLastLogin(int $id)
    {
        $this->db->update('user', ['last_login_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    public function block(User $user): void
    {
        $this->db->update('user', ['is_blocked' => 1], ['id' => $user->id]);
    }

    public function createFromPerco(int $badgeNumber): ?User
    {
        $data = $this->perco->getUserByBadge($badgeNumber);
        return $this->createUser($data);
    }

    public function createFromLdap(array $ldapData, string $password): ?User
    {
        $fullName = $ldapData['displayname'] ?? $ldapData['cn'] ?? null; // Для поиска в PERCo-Web
        $divisionName = $this->ldap->extractDivisionFromDn($ldapData['distinguishedname'] ?? ''); // Для уточнения поиска в PERCo-Web
        $positionName = $ldap['title'] ?? null;

        $percoData = $this->perco->findUserByName($fullName, $divisionName);

        $divisionId = $percoData['division_id'] ?? $this->resolveDictionaryEntryId('division', $divisionName) ?? null;
        $positionId = $percoData['position_id'] ?? $this->resolveDictionaryEntryId('position', $positionName) ?: null;

        $percoData['login'] = strtolower($ldapData['samaccountname']);
        $percoData['password'] = $password;
        $percoData['source'] = 'ldap';

        if (empty($percoData['email']) && !empty($ldapData['mail'])) {
            $percoData['email'] = $ldapData['mail'];
        }

        if (empty($percoData['division_id']) && $divisionId) {
            $percoData['division_id'] = $divisionId;
        }

        if (empty($percoData['position_id']) && $positionId) {
            $percoData['position_id'] = $positionId;
        }

        return $this->createUser($percoData);
    }

    public function createUser(array $data): ?User
    {
        // file_put_contents(PATH_LOGS . 'control_user_create.log', print_r($data, true) . PHP_EOL, FILE_APPEND);
        $login = strtolower(trim((string)($data['login'] ?? '')));
        $pass_hash = PasswordManager::hash($data['password']);
        $roleId = $this->roleManager->assignRole($data['positionId']);
        $user = new User([
            'login' => $login, 
            'password' => $pass_hash,
            'name' => $data['name'],
            'email' => $data['email'],
            'company_id' => $data['company_id'],
            'division_id' => $data['division_id'],
            'position_id' => $data['position_id'],
            'perco_id' => $data['perco_id'],
            'card_number' => $data['card_number'],
            'tabel_number' => $data['tabel_number'],
            'role_id' => $roleId,
            'is_active' => 1,
            'is_blocked' => 0,
            'birth_date' => $data['birth_date'],
            'mobyle' => $data['mobyle'],
            'source' => $data['source']
        ]);
        $this->create($user);
        return $this->findByLogin($login);
    }

    public function resolveDictionaryEntryId(string $dict, string $value): ?int
    {
        if (!$value) return null;
        $row = $this->db->first($dict, ['name' => $value]);
        if ($row) return (int)$row['id'];
        return null;
    }

    public function getDivisionName(int $id): ?string
    {
        if (!$id) return null;
        $row = $this->db->first('division', ['id' => $id]);
        if ($row) return (string)$row['name'];
        return null;
    }

    public function getPositionName(int $id): ?string
    {
        if (!$id) return null;
        $row = $this->db->first('position', ['id' => $id]);
        if ($row) return (string)$row['name'];
        return null;
    }
}