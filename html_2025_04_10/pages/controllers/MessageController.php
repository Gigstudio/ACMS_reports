<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Controller;

class MessageController extends Controller
{
    public function error($code, $params): void
    {
        $data = [
            'error_code' => $code ?? 500,
            'title' => "{$params['title']} ($code)",
            'message' => $params['message'] ?? 'Неизвестная ошибка',
            'details' => $params['details'] ?? '',
            'query' => [],
        ];
    
        $this->render('error', $data, []);
    }

    public function fatal($code, $params){
        $data = [
            'title' => "Ошибка ".$code,
            'content' => 'error',
            'error_code' => $code ?? 500,
            'error_message' => $params['error_message'] ?? 'Undefined error',
            'details' => $params['detailes'] ?? 'Undefined error',
            'query' => [],
        ];
    
        $this->render('error', $data, []);
    }
}
