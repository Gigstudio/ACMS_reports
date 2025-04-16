<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Engine\Block;
use GigReportServer\System\Engine\Application;

class HomeController extends Controller
{
    public function index($data): void{
        $ldap = Application::getInstance()->ldapClient;
        $data['userinfo'] = $ldap->getUserData('g.chirikov');
        // $check_ldap = 

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('content', $data);
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);
        $page = Block::make('layouts/default', ['title' => 'CRM-панель'])
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
            ]);

        $this->render($page);
    }
}
