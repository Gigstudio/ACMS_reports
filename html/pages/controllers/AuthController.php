<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Services\AuthManager;

class AuthController extends Controller
{
    public function login($data){
        $username = $data['login'] ?? null;
        $password = $data['pass'] ?? null;
    
        if (!$username || !$password) {
            return [
                'status' => 'error',
                'reason' => 'missing_fields',
                'message' => 'Необходимо ввести логин и пароль.'
            ];
        }
    
        try {
            $auth = new AuthManager();
            $result = $auth->authenticate($username, $password);
    
            if ($result && isset($result['user'])) {
                $user = $result['user'];
                return [
                    'status' => 'success',
                    'message' => 'Успешная авторизация.',
                    'user' => [
                        'login' => $user['login'],
                        'email' => $user['email'] ?? null,
                        'full_name' => $user['full_name'] ?? null
                    ]
                ];
            }

            return [
                'status' => 'error',
                'reason' => $result['reason'] ?? 'unknown',
                'message' => $result['message'] ?? 'Ошибка авторизации.'
            ];

        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'reason' => 'exception',
                'message' => 'Системная ошибка: ' . $e->getMessage()
            ];
        }
    }

    public function register($data){
        return [
            'status' => 'success',
            'message' => 'Временно недоступно'
        ];
    }

    public function logout() {
        return [
            'status' => 'success',
            'message' => 'Выход выполнен'
        ];
    }}
