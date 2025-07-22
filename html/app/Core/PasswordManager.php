<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class PasswordManager
{
    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }
}
