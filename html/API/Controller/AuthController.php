<?php
namespace GIG\Api\Controller;

use Throwable;

defined('_RUNKEY') or die;

use GIG\Core\Request;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\AuthManager;

class AuthController extends ApiController
{
    protected AuthManager $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthManager();
    }

    public function getmodal(Request $request): void
    {
        include PATH_VIEWS . 'partials/modal_login.php';
        exit;
    }

    public function login()
    {
        $data = $this->request->getBody();
        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if (!$login || !$password) {
            throw new GeneralException(
                'Логин и пароль обязательны',
                400,
                [
                    'reason' => !$login ? 'missing_login' : 'missing_password',
                    'detail' => 'Попытка входа без логина или пароля'
                ]
            );
        }
        try {
            $result = $this->auth->authenticate($login, $password, true);
            $token = $result['token'];
            $user  = $result['user'];
            $this->success([
                'user' => $user->toArray(['id', 'login', 'name', 'email', 'role_id', 'source']),
                'token' => $token
            ]);
        } catch (Throwable $e) {
            throw new GeneralException(
                $e->getCode() == 401 ? 'Неверный пароль' : ($e->getCode() == 404 ? 'Пользователь не найден' : 'Сбой авторизации'),
                $e->getCode() ?: 500,
                [
                    'reason' => $e->getCode() == 401 ? 'invalid_password' : ($e->getCode() == 404 ? 'not_found' : ''),
                    'detail' => $e->getMessage()
                ]
            );
        }
    }

    public function badgeCheck()
    {
        $badge = trim((string)($this->request->getBody()['badge'] ?? ''));

        if (!$badge) {
            throw new GeneralException(
                'Не указан номер пропуска',
                400,
                [
                    'reason' => 'missing_badge',
                    'detail' => 'Попытка запроса регистрации без пропуска'
                ]
            );
        }

        try {
            $result = $this->auth->checkBadge($badge);
            $user  = $result['user'];
            $check = $result['check'];

            if ($check['available']) {
                $this->success(['user' => $user, 'check' => $check]);
            } else {
                throw new GeneralException(
                    'Логин уже занят',
                    403,
                    [
                        'reason'    => 'login_taken',
                        'data' => [
                            'user'      => $user,
                            'check'     => $check,
                            // 'login'     => $user['login'],
                            // 'name'      => $check['name'] ?? null,
                            // 'email'     => $check['email'] ?? null,
                            // 'company'   => $check['company'] ?? null,
                            // 'division'  => $check['division'] ?? null,
                            // 'position'  => $check['position'] ?? null,
                            // 'source'    => $check['source'] ?? null
                        ]
                    ]
                );
            }
        } catch (Throwable $e) {
            $detail = $e instanceof GeneralException ? $e->getExtra()['data'] : '';
            // file_put_contents(PATH_LOGS . 'auth_check.log', print_r($detail, true), FILE_APPEND);
            throw new GeneralException(
                $e->getMessage(),
                $e->getCode() ?: 500,
                [
                    'reason' => $e->getCode() == 404 ? 'not_found' : ($e->getCode() == 403 ? 'login_taken' : ''),
                    'data' => $detail
                ]
            );
        }
    }

    public function loginCheck() {
        $candidat = $this->request->getBody()['candidate'] ?? null;
        // file_put_contents(PATH_LOGS . 'candidate_check.log', print_r($candidat, true), FILE_APPEND);
        $login = trim((string)($candidat['login'] ?? ''));
        if (!$login) {
            throw new GeneralException(
                'Ошибка при генерации логина',
                400,
                [
                    'reason' => 'missing_login',
                    'detail' => 'Попытка запроса регистрации без логина'
                ]
            );
        }

        try {
            $result = $this->auth->checkLogin($candidat);
            $user  = $result['user'];
            $check = $result['check'];

            if ($check['available']) {
                $this->success(['user' => $user, 'check' => $check]);
            } else {
                throw new GeneralException(
                    'Логин уже занят',
                    403,
                    [
                        'reason' => 'login_taken',
                        'data' => [
                            'user'      => $user,
                            'check'     => $check,
                            // 'login'     => $login,
                            // 'name'      => $check['name'] ?? null,
                            // 'email'     => $check['email'] ?? null,
                            // 'company'   => $check['company'] ?? null,
                            // 'division'  => $check['division'] ?? null,
                            // 'position'  => $check['position'] ?? null,
                            // 'source'    => $check['source'] ?? null
                        ]
                    ]
                );
            }
        } catch (Throwable $e) {
            $detail = $e instanceof GeneralException ? $e->getExtra()['data'] : '';
            // file_put_contents(PATH_LOGS . 'auth_check.log', print_r($detail, true), FILE_APPEND);
            throw new GeneralException(
                $e->getMessage(),
                $e->getCode() ?: 500,
                [
                    'reason' => $e->getCode() == 404 ? 'not_found' : ($e->getCode() == 403 ? 'login_taken' : ''),
                    'data' => $detail
                ]
            );
        }
    }

    public function register()
    {
        $data = $this->request->getBody();
        // file_put_contents(PATH_LOGS . 'reg_user.log', print_r($data, true));

        $login = $data['newData']['login'] ?? '';
        $email = $data['newData']['email'] ?? '';
        $password = $data['newData']['password'] ?? '';

        if (!$login || !$email|| !$password) {
            throw new GeneralException(
                'Логин, email и пароль обязательны',
                400,
                [
                    'reason' => !$login ? 'missing_login' : (!$email ? 'missing_email' : 'missing_password'),
                    'detail' => 'Попытка регистрации без логина, email или пароля'
                ]
            );
        }
        
        try {
            $result = $this->auth->register($data['newData']);
            // $token = $result['token'];
            $user  = $result['user'];
            $this->success([
                'user' => $user->toArray(['id', 'login', 'name', 'email', 'role_id', 'source']),
                // 'token' => $token
            ]);
        } catch (Throwable $e) {
            throw new GeneralException(
                $e->getCode() == 401 ? 'Неверный пароль' : ($e->getCode() == 404 ? 'Пользователь не найден' : 'Сбой авторизации'),
                $e->getCode() ?: 500,
                [
                    'reason' => $e->getCode() == 401 ? 'missing_password' : ($e->getCode() == 404 ? 'missing_login' : ''),
                    'detail' => $e->getMessage()
                ]
            );
        }
    }

    // public function logout(): void
    // {
    //     $this->auth->logout();
    //     $this->success([
    //         'status' => 'success',
    //         'message' => 'Вы вышли из системы.'
    //     ]);
    // }
}