<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Repository\LocalUserRepository;
use GIG\Core\Application;
use GIG\Infrastructure\Persistence\LdapClient;
use GIG\Domain\Entities\User;
use GIG\Core\PasswordManager;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Entities\Event;

class AuthManager
{
    protected LocalUserRepository $users;
    protected ?LdapClient $ldap = null;

    public function __construct()
    {
        $this->users = new LocalUserRepository();
        $this->ldap = Application::getInstance()->getLdapClient();
    }

public function authenticate(string $login, string $password): User
{
    // 1. Пытаемся найти пользователя в локальной базе
    $user = $this->users->findByLogin($login);

    // 2. Если нет в локальной базе — ищем в LDAP
    if (!$user) {
        // 2.1. Поиск пользователя в LDAP
        $ldapUser = $this->ldap->getUserData($login);

        if (empty($ldapUser)) {
            throw new GeneralException("Пользователь не найден", 404, [
                'detail' => "Пользователь '$login' отсутствует в системе и в LDAP"
            ]);
        }

        // 2.2. Попытка авторизации через bind
        $bindOk = $this->ldap->bindUser($login, $password);

        if (!$bindOk) {
            throw new GeneralException("Неверный логин или пароль", 401, [
                'detail' => "Введён неверный пароль для пользователя '$login' (LDAP)"
            ]);
        }

        // 2.3. Автоматическая регистрация локального пользователя (опционально — заполняй по логике)
        // Здесь надо создавать нового User и добавлять в базу:
        $user = $this->users->createFromLdap($ldapUser, $password);
        // $this->users->create($user);
        return $user;

        // throw new GeneralException("Логика автосоздания пользователя ещё не реализована", 501, [
        //     'detail' => "LDAP-пользователь '$login' найден и прошёл bind, но автоматическая регистрация не реализована"
        // ]);
    }

    // 3. Проверяем активность пользователя
    if (!$user->isActive()) {
        throw new GeneralException("Пользователь заблокирован или не активен", 403, [
            'detail' => "Учётная запись '$login' отключена"
        ]);
    }

    // 4. Если это LDAP-пользователь — проверяем пароль через LDAP
    $isLdap = ($user->source === 'ldap');
    if ($isLdap && $this->ldap) {
        try {
            $ok = $this->ldap->bindUser($login, $password);
            if ($ok) {
                // Можно обновить локальный хэш (опционально)
                // $this->users->updatePassword($login, password_hash($password, PASSWORD_DEFAULT));
                EventManager::logParams(Event::INFO, self::class, "Успешная аутентификация через LDAP для '$login'");
                return $user;
            } else {
                throw new GeneralException("Неверный логин или пароль", 401, [
                    'detail' => "LDAP bind для $login не удался"
                ]);
            }
        } catch (\Throwable $e) {
            EventManager::logParams(Event::WARNING, self::class, "LDAP недоступен, fallback к локальному паролю", [
                'exception' => $e->getMessage()
            ]);
            // FALLBACK к локальному хэшу
        }
    }

    // 5. Проверяем локальный пароль (если LDAP не сработал или это local user)
    if (password_verify($password, $user->password)) {
        EventManager::logParams(Event::INFO, self::class, "Локальная авторизация для '$login' (LDAP fallback)");
        return $user;
    }

    // 6. Если все проверки не прошли
    throw new GeneralException("Неверный логин или пароль", 401, [
        'detail' => 'Пароль не подошёл ни к одному из способов'
    ]);
}}