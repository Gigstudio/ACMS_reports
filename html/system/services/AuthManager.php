<?php
namespace GigReportServer\System\Services;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Application;
use GigReportServer\System\Engine\PasswordValidator;
use GigReportServer\System\Services\LDAPService;
use GigReportServer\System\Services\UserRepository;

class AuthManager
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function authenticate(string $login, string $password): ?array
    {
        $ldapConfig = Application::getInstance()->getConfig('ldap');
        $user = $this->users->findByLogin($login);

        if ($user && !empty($user['password']) && PasswordValidator::verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            return ['user' => $user];
        }

        // Попытка через LDAP
        $ldap = LDAPService::safeConnect($ldapConfig);
        if (!$ldap) {
            return [
                'reason' => 'ldap_unreachable',
                'message' => 'LDAP временно недоступен.'
            ];
        }

        $data = $ldap->getUserData($login);
        if (!empty($data[0])) {
            // Попытка bind с введённым паролем
            $dn = $data[0]['dn'][0] ?? null;
            if (!$dn) {
                return [
                    'reason' => 'ldap_dn_missing',
                    'message' => 'LDAP: DN не найден в записи пользователя.'
                ];
            }
            
            $domain = strtolower(
                preg_replace('/DC=/', '', $ldapConfig['ldap_dn'])
            );
            $domain = str_replace(',', '.', $domain);
            $upn = "$login@$domain";
            $bind = @ldap_bind($ldap->getConnection(), $upn, $password);

            if (!$bind) {
                return [
                    'reason' => 'ldap_invalid_password',
                    'message' => 'LDAP: неверный доменный пароль. '// . ldap_error($ldap->getConnection())
                ];
            }

            // Если пользователь уже есть в локальной БД — используем его
            if ($user) {
                $_SESSION['user'] = $user;
                return ['user' => $user];
            }

            // Иначе создаём нового из LDAP
            $user = $this->users->createFromLdap($data[0]);
            $_SESSION['user'] = $user;
            return ['user' => $user];
       }

        return [
            'reason' => 'not_found',
            'message' => 'Пользователь не найден.'
        ];
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
