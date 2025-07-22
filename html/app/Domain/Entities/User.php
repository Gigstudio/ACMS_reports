<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class User extends Entity
{
    public int $id;
    public string $login;
    public string $password;
    public ?string $email = null;
    public ?string $tabel_number = null;
    public int|string $perco_id;
    public ?string $card_number = null;
    public int $role_id = 1;
    public bool $is_active = true;
    public bool $is_blocked = false;
    public string $created_at;
    public string $source = 'perco';
    public ?string $last_login_at = null;

    public function isActive(): bool
    {
        return $this->is_active && !$this->is_blocked;
    }
}