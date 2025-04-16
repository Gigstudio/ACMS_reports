<?php
namespace GigReportServer\Pages\Controllers;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Controller;
use GigReportServer\System\Engine\Block;

class MessageController extends Controller
{
    public function error($code, $data): void{
        $this->setStatus($code);

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('error', $data);
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
