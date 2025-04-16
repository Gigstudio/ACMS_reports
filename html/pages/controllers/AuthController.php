<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Engine\Application;

class AuthController extends Controller
{
    public function login($data){
        $username = $data['login'] ?? null;
        $password = $data['pass'] ?? null;
        
        try{
            $ldap = Application::getInstance()->ldapClient;
            $info = $ldap->getUserData($username);
            return [
                'status' => 'success',
                'message' => 'Данные из LDAP получены',
                'data' => $info
            ];
        } catch(\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка подключения к LDAP'
            ];
        }
    }

    public function register($data){
        return json_encode([
            'status' => 'success',
            'message' => 'Временно недоступно'
        ]);
    }
}
