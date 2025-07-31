<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class UserToken extends Entity
{
    public int $id;
    public int $user_id;
    public string $token;
    public string $created_at;
    public ?string $expires_at = null;
    public ?string $ip_address = null;
    public ?string $user_agent = null;
    public string $source = 'sse';
    public bool $is_revoked = false;

    /**
     * Проверка: активен ли токен
     */
    public function isValid(): bool
    {
        if ($this->is_revoked) return false;
        if ($this->expires_at && strtotime($this->expires_at) < time()) return false;
        return true;
    }
}
