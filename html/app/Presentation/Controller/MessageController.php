<?php
namespace GIG\Presentation\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\Block;

class MessageController extends Controller
{
    public function error(int $code, array|string $data): void{
        $this->setStatus($code);

        if (!is_array($data)) {
            $data = [
                'message' => $data,
                'title' => 'Ошибка',
                'detail' => null
            ];
        }
    
        $data = array_merge([
            'message' => 'Ошибка',
            'title' => 'Ошибка',
            'detail' => null
        ], $data);
    
        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('blocks/error', $data);
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
