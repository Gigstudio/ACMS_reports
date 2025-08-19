<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class User extends Entity
{
    public int $id;
    public string $login;
    public string $password;
    public string $name;
    public ?string $email = null;
    public int $company_id;
    public int $division_id;
    public int $position_id;
    public int $perco_id;
    public ?string $card_number = null;
    public ?string $tabel_number = null;
    public int $role_id = 1;
    public int $is_active = 1;
    public int $is_blocked = 0;
    public ?string $birth_date = null;
    public ?string $mobyle = null;
    public string $source = 'local';
    public string $created_at;
    public ?string $last_login_at = null;

    public function isActive(): bool
    {
        return $this->is_active && !$this->is_blocked;
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getInsertableColumns(): array
    {
        return [
            'login', 
            'password',
            'name',
            'email',
            'company_id',
            'division_id',
            'position_id',
            'perco_id',
            'card_number',
            'tabel_number',
            'role_id',
            'is_active',
            'is_blocked',
            'birth_date',
            'mobyle',
            'source',
            'created_at'
        ];
    }

    public function getUpdatableColumns(): array
    {
        return [
            'password',
            'email',
            'name',
            'table_number',
            'perco_id',
            'card_number',
            'company_id',
            'division_id',
            'position_id',
            'role_id',
            'is_active',
            'is_blocked',
            'birth_date',
            'mobyle',
            'source',
            'last_login_at'
        ];
    }
}