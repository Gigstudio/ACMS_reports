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
                'user' => $user->toArray(['id', 'login', 'name', 'role_id', 'source']),
                'token' => $token
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

    public function logout(): void
    {
        $this->auth->logout();
        $this->success([
            'status' => 'success',
            'message' => 'Вы вышли из системы.'
        ]);
    }
}