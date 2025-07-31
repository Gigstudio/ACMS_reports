<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Domain\Entities\UserToken;
use GIG\Domain\Entities\User;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Persistence\MySQLClient;

class UserTokenRepository
{
    protected MySQLClient $db;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->columns = $this->getColumnNames('user_token');
    }

    private function getColumnNames(string $table): array
    {
        $result = $this->db->describeTable($table);
        return array_column($result, 'Field');
    }

    /**
     * Создать новый токен для пользователя
     */
    public function createForUser(User $user, string $source = 'sse', ?int $ttlSeconds = 1800): string
    {
        $token = bin2hex(random_bytes(32)); // 64 символа

        $record = [
            'user_id'     => $user->id,
            'token'       => $token,
            'source'      => $source,
            'created_at'  => date('Y-m-d H:i:s'),
            'expires_at'  => $ttlSeconds ? date('Y-m-d H:i:s', time() + $ttlSeconds) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'is_revoked'  => 0,
        ];

        $this->validateFields($record);
        $this->db->insert('user_token', $record);

        return $token;
    }

    /**
     * Получить пользователя по токену
     */
    public function findUserByToken(string $token): ?User
    {
        $row = $this->db->first('user_token', ['token' => $token]);

        if (!$row) return null;

        $entity = UserToken::fromArray($row);
        if (!$entity->isValid()) return null;

        $userRow = $this->db->first('user', ['id' => $entity->user_id, 'is_active' => 1]);
        return $userRow ? User::fromArray($userRow) : null;
    }

    /**
     * Отозвать токен
     */
    public function revoke(string $token): void
    {
        $this->db->update('user_token', ['is_revoked' => 1], ['token' => $token]);
    }

    private function validateFields(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->columns, true)) {
                throw new GeneralException("Недопустимое поле '$key' при сохранении токена.", 500, [
                    'detail' => "Поле '$key' не является полем таблицы user_token"
                ]);
            }
        }
    }
}
