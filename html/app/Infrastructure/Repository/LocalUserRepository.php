<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\PasswordManager;
use GIG\Domain\Entities\User;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\PercoManager;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Services\RoleManager;
use GIG\Infrastructure\Persistence\MySQLClient;

class LocalUserRepository
{
    protected MySQLClient $db;
    protected PercoManager $percoManager;
    protected RoleManager $roleManager;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->percoManager = new PercoManager();
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
        $data = $user->getInsertableColumns();
        unset($data['id']);
        $this->db->insert('user', $data);
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

    public function createFromLdap(array $ldap, string $password): ?User{
        $fullName = $ldap['displayname'] ?? '';
        $divisionName = $this->extractDivisionFromDn($ldap['distinguishedname'] ?? '');
        $positionName = $ldap['title'] ?? null;

        $percoData = $this->percoManager->findUserByName($fullName, $divisionName);

        $login = strtolower($ldap['samaccountname'] ?? '');
        $pass_hash = PasswordManager::hash($password);
        $name = $percoData['name'];
        $email = $ldap['mail'] ?? $percoData['email'] ?? null;
        $companyId = $percoData['company_id'] ?: 0;
        $divisionId = $percoData['division_id'] ?? $this->resolveDictionaryEntry('division', $divisionName) ?? null;
        $positionId = $percoData['position_id'] ?? $this->resolveDictionaryEntry('position', $positionName) ?: null;
        $perco_id = $percoData['perco_id'] ?? null;
        $cardNumber = $percoData['card_number'] ?: null;
        $tabelNumber = $percoData['tabel_number'] ?: null;
        $roleId = $this->roleManager->assignRole($positionId);
        $birthDate = $percoData['birth_date'] ?: null;
        $mobyle = $percoData['mobyle'] ?: null;

        $user = new User([
            'login' => $login, 
            'password' => $pass_hash,
            'name' => $name,
            'email' => $email,
            'company_id' => $companyId,
            'division_id' => $divisionId,
            'position_id' => $positionId,
            'perco_id' => $perco_id,
            'card_number' => $cardNumber,
            'tabel_number' => $tabelNumber,
            'role_id' => $roleId,
            'is_active' => 1,
            'is_blocked' => 0,
            'birth_date' => $birthDate,
            'mobyle' => $mobyle,
            'source' => 'ldap'
        ]);
        $this->db->updateOrInsert('user', $user->toArray($user->getInsertableColumns()), ['login' => $login]);
        return $this->findByLogin($login);
    }

    private function extractDivisionFromDn(string $dn): ?string
    {
        if (preg_match_all('/OU=([^,]+)/u', $dn, $matches) && isset($matches[1][0])) {
            return trim($matches[1][0]);
        }
        return null;
    }

    public function resolveDictionaryEntry(string $dict, string $value): ?int
    {
        if (!$value) return null;
        $row = $this->db->first($dict, ['name' => $value]);
        if ($row) return (int)$row['id'];
        return null;
    }
}