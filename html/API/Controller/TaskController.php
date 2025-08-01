<?php
namespace GIG\Api\Controller;

defined('_RUNKEY') or die;

use GIG\Domain\Services\TaskManager;

class TaskController extends ApiController
{
    protected TaskManager $manager;

    public function __construct()
    {
        parent::__construct();
        $this->manager = new TaskManager($this->app->getMysqlClient());
    }

    // Список задач
    public function list()
    {
        $filter = []; // добавить фильтрацию, если надо
        $limit  = (int)($this->request->getQueryParam('limit', 100));
        $tasks = $this->manager->getTasks($filter, $limit);
        $this->success(['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }

    // Получить одну задачу
    public function view()
    {
        $id = (int)($this->request->getRouteParams()['id'] ?? 0);
        $task = $this->manager->getTask($id);
        $this->success(['task' => $task->toArray()]);
    }

    // Создать задачу
    public function create()
    {
        $body = $this->request->getBody();

        $type           = $body['type'] ?? null;
        $params         = $body['params'] ?? [];
        $userId         = $body['user_id'] ?? null;
        $executionType  = strtoupper(trim($body['execution_type'] ?? 'ONCE'));
        $interval       = isset($body['interval_minutes']) ? (int)$body['interval_minutes'] : null;
        $name           = $body['name'] ?? null;

        if (!$type) {
            $this->error("Не указан тип задачи");
        }

        if (!in_array($executionType, ['ONCE', 'RECURRING'])) {
            $this->error("Недопустимый тип запуска: $executionType");
        }

        $task = $this->manager->createTask($type, $name, $params, $userId, $executionType, $interval);
        $this->success(['task' => $task->toArray()], "Создана фоновая задача");
    }

    // Удалить задачу
    public function delete()
    {
        $id = (int)($this->request->getRouteParams()['id'] ?? 0);
        $this->manager->deleteTask($id);
        $this->success([], "Задача удалена");
    }

    // Отменить задачу (пометить как aborted)
    public function cancel()
    {
        $id = (int)($this->request->getRouteParams()['id'] ?? 0);
        $this->manager->updateTask($id, ['status' => 'ABORTED']);
        $this->success([], "Задача отменена");
    }
}
