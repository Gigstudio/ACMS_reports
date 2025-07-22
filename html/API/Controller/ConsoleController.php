<?php
namespace GIG\Api\Controller;

use GIG\Domain\Entities\UserSettings;

defined('_RUNKEY') or die;

use GIG\Core\Console;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;
use GIG\Infrastructure\Repository\EventRepository;
use GIG\Infrastructure\Repository\UserSettingsRepository;

/**
 * API-контроллер для работы с консолью сообщений.
 * Методы: get, add, clear, count, last.
 */
class ConsoleController extends ApiController
{
    protected EventRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new EventRepository();
    }
    /**
     * Получить все сообщения консоли (опционально с фильтром по level)
     * GET /api/console/get?filterLevel=2
     */
    public function get()
    {
        $level = (int)($this->param('level') ?? 0);
        $fromId = (int)($this->param('fromId') ?? 0);

        if (!$fromId && $this->user) {
            $settings = (new UserSettingsRepository())->findByUserId($this->user->id);
            $fromId = (int)($settings['console_first_id'] ?? 0);
        }

        $events = $this->repository->findAll();
        $events = array_filter($events, static function (Event $event) use ($fromId, $level): bool {
            return $event->id > $fromId && $event->type >= $level;
        });

        $messages = array_map(static function (Event $event) {
            return EventManager::toArray($event);
        }, $events);

        // $messages = array_map(static function (Event $event): array {
        //     return [
        //         'time'    => date('Y-m-d H:i:s', $event->timestamp),
        //         'class'   => EventManager::getClass($event->type),
        //         'source'  => EventManager::extractSource($event->source),
        //         'message' => $event->message,
        //         'detail'  => $event->data['extra']['detail'] ?? '',
        //     ];
        // }, $events);

        $this->success(['messages' => $messages], 'Сообщения получены: всего сообщений - ' . count($messages));
    }

    /**
     * Добавить сообщение в консоль вручную (для теста/отладки)
     * POST /api/console/add
     */
    public function add()
    {
        $message = trim((string)($this->param('message') ?? ''));
        if ($message === '') {
            $this->error('Сообщение не может быть пустым', 400);
            return;
        }

        $source = $this->param('source') ?? 'api';
        if (!is_string($source) || strlen($source) > 128) {
            $this->error('Некорректный источник сообщения', 400);
            return;
        }

        $level = (int)($this->param('level') ?? 0);
        if ($level < 0 || $level > 4) {
            $this->error('Неизвестный тип события', 400);
            return;
        }

        $detail = $this->param('detail') ?? '';

        EventManager::logParams($level, $source, $message, [
            'extra' => ['detail' => $detail]
        ]);

        $this->success([], 'Сообщение добавлено');
    }

    /**
     * Очистить сообщения консоли
     * POST /api/console/clear
     */
    public function clear()
    {
        $lastId = $this->repository->getLastId();
        if ($this->user) {
            $settingsRepo = new UserSettingsRepository();
            $settings = $settingsRepo->findByUserId($this->user->id);
            if (!$settings) {
                $settings = new UserSettings([
                    'user_id' => $this->user->id,
                ]);
            }
            $settings->__set('console_first_id', $lastId);
            $settingsRepo->save($settings);
        }
        $this->success(['firstEventId' => $lastId], 'Консоль очищена');
    }

    /**
     * Получить количество сообщений в консоли
     * GET /api/console/count
     */
    public function count()
    {
        $count = $this->repository->count();
        $this->success(['count' => $count]);
    }

    /**
     * Получить последнее сообщение консоли
     * GET /api/console/last
     */
    public function last()
    {
        $msg = $this->repository->getLastMessage();
        $this->success(['message' => $msg]);
    }
}
