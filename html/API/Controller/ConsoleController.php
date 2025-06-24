<?php
namespace GIG\Api\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Console;
use GIG\Api\ApiAnswer;

/**
 * API-контроллер для работы с консолью сообщений.
 * Методы: get, add, clear, count, last.
 */
class ConsoleController extends ApiController
{
    /**
     * Получить все сообщения консоли (опционально с фильтром по level)
     * GET /api/console/get?filterLevel=2
     */
    public function get()
    {
        $filterLevel = $this->param('level');
        $messages = Console::getMessages($filterLevel ?? 0);
        $this->success(['messages' => $messages]);
    }

    /**
     * Добавить сообщение в консоль вручную (для теста/отладки)
     * POST /api/console/add
     */
    public function add()
    {
        $msg = [
            'time'    => $this->param('time') ?? date('Y-m-d H:i:s'),
            'class'   => $this->param('class') ?? 'info',
            'source'  => $this->param('source') ?? 'api',
            'message' => $this->param('message') ?? '',
            'detail'  => $this->param('detail') ?? '',
        ];
        if ($this->param('level') !== null) {
            $msg['level'] = (int)$this->param('level');
        }
        Console::setMessage($msg);
        $this->success(['message' => 'ok']);
    }

    /**
     * Очистить сообщения консоли
     * POST /api/console/clear
     */
    public function clear()
    {
        Console::clearMessages();
        $this->success(['message' => 'cleared']);
    }

    /**
     * Получить количество сообщений в консоли
     * GET /api/console/count
     */
    public function count()
    {
        $count = Console::count();
        $this->success(['count' => $count]);
    }

    /**
     * Получить последнее сообщение консоли
     * GET /api/console/last
     */
    public function last()
    {
        $msg = Console::getLastMessage();
        $this->success(['message' => $msg]);
    }
}
