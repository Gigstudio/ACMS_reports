<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\PasswordManager;
use GIG\Infrastructure\Persistence\LdapClient;
use GIG\Infrastructure\Repository\LocalUserRepository;
use GIG\Infrastructure\Repository\UserTokenRepository;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\PercoManager;

class AuthManager
{
    protected LocalUserRepository $users;
    protected ?LdapClient $ldap = null;
    protected ?PercoManager $perco = null;

    public function __construct()
    {
        $this->users = new LocalUserRepository();
        $this->ldap = Application::getInstance()->getLdapClient();
        $this->perco = new PercoManager();
    }

    public function authenticate(string $login, string $password, bool $withToken = false): array
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
                throw new GeneralException("Неверный пароль", 401, [
                    'detail' => "Введён неверный пароль для пользователя '$login' (LDAP)"
                ]);
            }

            // 2.3. Автоматическая регистрация локального пользователя
            $user = $this->users->createFromLdap($ldapUser, $password);
            // $user = $this->users->createFromLdap($login, $password);

            EventManager::logParams(Event::INFO, self::class, "Успешная аутентификация после создания локального пользователя '$login' из LDAP");
            if ($withToken) {
                $token = (new UserTokenRepository())->createForUser($user, 'ldap');
                return ['user' => $user, 'token' => $token];
            }
            return ['user' => $user];
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
                    if ($withToken) {
                        $token = (new UserTokenRepository())->createForUser($user, 'ldap');
                        return ['user' => $user, 'token' => $token];
                    }
                    return ['user' => $user];
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
        if (PasswordManager::verify($password, $user->password)) {
            EventManager::logParams(Event::INFO, self::class, "Локальная авторизация для '$login' (LDAP fallback)");
            if ($withToken) {
                $token = (new UserTokenRepository())->createForUser($user, 'local');
                return ['user' => $user, 'token' => $token];
            }
            return ['user' => $user];
        }

        // 6. Если все проверки не прошли
        throw new GeneralException("Неверный логин или пароль", 401, [
            'detail' => 'Пароль не подошёл ни к одному из способов'
        ]);
    }

    public function checkBadge(string $badge): array
    {
        $candidate = $this->perco->getUserByBadge($badge);
        if (!$candidate) {
            throw new GeneralException("Пользователь PERCo-Web не найден.", 404, [
                'detail' => "Пользователь PERCo-Web с номером пропуска $badge не найден."
            ]);
        }

        $candidate['login'] = $this->fromFullName($candidate['name']);
        // file_put_contents(PATH_LOGS . 'register_debug.log', "BAGE checked. Candidate:" . PHP_EOL . print_r($candidate, true) . PHP_EOL, FILE_APPEND);
        return $this->checkLogin($candidate);
    }

    public function checkLogin(array $candidate): array
    {
        $login = mb_strtolower(trim($candidate['login']));

        $found = $this->users->findByLogin($login);
        if ($found) {
            $check = [
                'available' => false,
                'name'      => $found->getFullName(),
                'email'     => $found->getEmail(),
                // 'company'   => $this->users->getCompanyName($found->company_id),
                'division'   => $this->users->getDivisionName($found->division_id),
                'position'   => $this->users->getPositionName($found->position_id),
                'source'    => 'local'
            ];
        } else {
            $found = $this->ldap->findUser($login);
            if ($found) {
                $check = [
                    'available' => false,
                    'name'      => $found['displayname'] ?? $found['cn'] ?? null,
                    'email'     => $found['mail'] ?? null,
                    // 'company'   => $found['company'] ?? null,
                    'division'  => $found['department'] ?? null,
                    'position'  => $found['title'] ?? null,
                    'source'    => 'ldap',
                ];
                $candidate['source'] = 'ldap';
            } else {
                $check = [ 
                    'available' => true,
                ];
            }
        }
        $candidate['email'] = $check['email'];

        return ['user' => $candidate, 'check' => $check];
    }

    public function register(array $data)
    {
        $user = $this->users->createUser($data);

        EventManager::logParams(Event::INFO, self::class, "Успешное создание локального пользователя '{$data['login']}'");
        return ['user' => $user];
    }

    /**
     * Генерация логина по шаблону: и.фамилия
     */
    public function fromFullName(string $fio): string
    {
        $parts = explode(' ', mb_strtolower(trim($fio)));

        if (count($parts) < 2) {
            return self::translit($parts[0] ?? 'user');
        }

        $initial = mb_substr($parts[1], 0, 1);
        $last = $parts[0];

        return self::translit("{$initial}.{$last}");
    }

    /**
     * Простая транслитерация с поддержкой кириллицы
     */
    public static function translit(string $text): string
    {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i',
            'й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t',
            'у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y',
            'ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',' '=>'.','-'=>''
        ];

        return strtr($text, $map);
    }
}