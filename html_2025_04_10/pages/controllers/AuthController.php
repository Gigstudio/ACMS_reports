<?php
namespace GigReportServer\Pages\Controllers;

use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Engine\Application;
use GigReportServer\Pages\Models\UserModel;
use GigReportServer\System\Engine\Event;

defined('_RUNKEY') or die;

class AuthController extends Controller
{
    public array $statuses = [
        0 => 'Success'
    ];
    public function login($queryParams){
        // $data['layout'] = 'auth';
        $method = Application::$app->getRequest();
        if($method->isPost()){
            $data = $queryParams['query'];
            $userModel = new UserModel();
            $action = array_key_exists("reguser", $data) ? 'register' : (array_key_exists("user", $data) ? 'login' : 'undefined');
            $userModel->loadData($data);
            if($userModel->validate()){
                // $event = new Event(Event::EVENT_INFO, basename(str_replace('\\', '/', self::class)), $message);
            }
            $userModel->$action($data);
            // var_dump($queryParams);
            // var_dump($action);
            // return 'Posted';
        }
        $data['headmain'] = 'Сервер отчетов';
        $data['add_js'] = ['login'];
        $data['add_css'] = ['login'];

        $this->render('login', $data, $queryParams);
    }
}