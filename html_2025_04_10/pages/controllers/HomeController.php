<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Application;
use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Network\NetworkScaner;
use GigReportServer\API\PercoClient;

class HomeController extends Controller
{
    public function index($queryParams){
        $data['headmain'] = 'Сервер отчетов';
        $data['query'] = $queryParams;

        $this->render('home', $data, $queryParams);
    }

    public function api_test($queryParams){
        $data['headmain'] = 'Тестирование API';
        // $data['add_js'] = ['apiclient', 'percoClient', 'ldapClient', 'useapi'];

        $method = Application::$app->getRequest();
        if($method->isPost()){
            // try {
            //     $percoClient = new PercoClient();
            //     $response = $percoClient->getStaffList();
            //     $data['response'] = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            // } catch (\Exception $e) {
            //     $data['response'] = "Ошибка: " . $e->getMessage();
            // }
            // $percoClient = new PercoClient();
            // $response = $percoClient->getStaffList();
            // var_dump($response);
        }
        $this->render('apitest', $data, $queryParams);
    }

    public function reports($queryParams){
        $data['query'] = $queryParams;
        $data['content'] = 'report';
        $data['headmain'] = 'Сервер отчетов';
        $data['mainmenu'] = [
            ['title' => 'Главная', 'link' => '/', 'icon' => ''],
            ['title' => 'Тестирование API', 'link' => '/api_test', 'icon' => ''],
            ['title' => 'Отчеты', 'link' => '/reports', 'icon' => ''],
        ];
        $data['sidemenu'] = [];
        $data['bottommenu'] = [
            ['title' => 'О проекте', 'link' => '/about', 'icon' => ''],
            ['title' => 'Контакты', 'link' => '/contact'],
        ];
        $this->render('report', $data, $queryParams);
    }

    public function about(){
        // $this->render('about', ['title' => 'О сервисе', 'message' => 'About system']);
    }

    public function exec_apitest($queryParams){
        // var_dump($params);
        $data['headmain'] = 'Тестирование API: Результат';
        $this->render('exec_apitest', $data, $queryParams['query']);
    }

    // public function error($params){
    //     $this::parent->error($params);
    // }
}
