<?php
namespace GigReportServer\System\Services;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Application;
use GigReportServer\System\Engine\PasswordValidator;
use GigReportServer\System\Services\LDAPService;
use GigReportServer\System\Services\UserRepository;

class AuthManager
{
    protected Application $app;
    protected UserRepository $users;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->users = new UserRepository();
    }

    public function authenticate(string $login, string $password): ?array
    {
        $user = $this->users->findByLogin($login);

        if (!$user) {
            $ldap = LDAPService::safeConnect($this->app->getConfig('ldap'));
            if ($ldap) {
                $data = $ldap->getUserData($login);
                if (!empty($data[0])) {
                    $user = $this->users->createFromLdap($data[0]);
                    // Здесь позже: сопоставление с PERCo
                }
            }
        }

        if (!$user || empty($user['password_hash'])) {
            return null;
        }

        if (!PasswordValidator::verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
