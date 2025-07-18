<?php
namespace GIG\Presentation\Controller;

use GIG\Core\Application;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\Request;
use GIG\Core\Block;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $modals = [
            'login' => Block::make('partials/modal_login', ['show' => false])
        ];

        $data['title'] = 'Добро пожаловать!';

        /*
        Данные для тестирования
        FireBird:
        */
        // $firebird = Application::getInstance()->getFirebirdClient();
        
        // $tables = $firebird->exec("SELECT TRIM(RDB\$RELATION_NAME) AS NAME FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0");
        // $tableNames = [];
        // foreach ($tables as $tbl) {
        //     $tableNames[] = $tbl['NAME'];
        // }
        // $data['firebird_tables'] = $tableNames;

        // $staff = $firebird->get('STAFF', [], ['*'], 200);
        // $data['staff'] = $staff;


        // НАЧАЛО РЕНДЕРА. Все переменные должны быть определены ДО этого блока.

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('blocks/home', $data);
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);
        $statusbar = Block::make('partials/statusbar', ['user' => 'Admin']);
        $modals = Block::make('partials/modals', ['modals' => $modals]);

        $page = Block::make('layouts/default', $data)
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
                'statusbar' => $statusbar,
                'modals' => $modals,
            ]);

        $this->render($page);
    }
}
