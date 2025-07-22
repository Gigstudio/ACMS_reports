<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class UserSettings extends Entity
{
    public int $id;
    public int $user_id;
    public int $console_first_id = null;
    public ?string $settings = null;
    public string $updated_at;
}
