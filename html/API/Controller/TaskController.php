<?php
namespace GIG\Api\Controller;

defined('_RUNKEY') or die;

use GIG\Domain\Services\BackgroundTaskManager;
use GIG\Core\Application;
use GIG\Core\Request;

class TaskController extends ApiController
{
    protected BackgroundTaskManager $manager;

    public function __construct()
    {
        parent::__construct();
        $this->manager = new BackgroundTaskManager(Application::getInstance()->getMysql());
    }

    // Список задач
    public function list(Request $request)
    {
        $filter = []; // добавить фильтрацию, если надо
        $limit  = (int)($request->getQueryParam('limit', 100));
        $tasks = $this->manager->getTasks($filter, $limit);
        $this->success(['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }

    // Получить одну задачу
    public function view(Request $request)
    {
        $id = (int)($request->getRouteParams()['id'] ?? 0);
        $task = $this->manager->getTask($id);
        $this->success(['task' => $task->toArray()]);
    }

    // Создать задачу
    public function create(Request $request)
    {
        $type   = $request->getBody()['type'] ?? null;
        $params = $request->getBody()['params'] ?? [];
        $userId = $request->getBody()['user_id'] ?? null;
        $task = $this->manager->createTask($type, $params, $userId);
        $this->success(['task' => $task->toArray()], "Создана фоновая задача");
    }

    // Удалить задачу
    public function delete(Request $request)
    {
        $id = (int)($request->getRouteParams()['id'] ?? 0);
        $this->manager->deleteTask($id);
        $this->success([], "Задача удалена");
    }

    // Отменить задачу (пометить как aborted)
    public function cancel(Request $request)
    {
        $id = (int)($request->getRouteParams()['id'] ?? 0);
        $this->manager->updateTask($id, ['status' => 'aborted']);
        $this->success([], "Задача отменена");
    }
}
