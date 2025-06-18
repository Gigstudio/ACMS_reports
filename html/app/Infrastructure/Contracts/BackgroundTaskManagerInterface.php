<?php
namespace GIG\Infrastructure\Contracts;

defined('_RUNKEY') or die;

use GIG\Domain\Entities\BackgroundTask;

/**
 * Контракт менеджера фоновых задач.
 * Абстрагирует создание, обновление, получение и мониторинг задач.
 */
interface BackgroundTaskManagerInterface
{
    /**
     * Создать новую фоновую задачу.
     * @param string $type  Тип задачи (например, 'sync_firebird', 'export_csv', ...).
     * @param array $params Параметры задачи (будут сериализованы в json).
     * @param int|null $userId Инициатор (если есть).
     * @return BackgroundTask ID созданной задачи.
     */
    public function createTask(string $type, array $params = [], ?int $userId = null): BackgroundTask;

    /**
     * Получить задачу по ID.
     * @param int $id
     * @return BackgroundTask|null
     */
    public function getTask(int $id): ?BackgroundTask;

    /**
     * Вернуть список задач с фильтрами (например, по статусу или типу).
     * @param array $filter
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTasks(array $filter = [], int $limit = 100): array;

    /**
     * Обновить статус и результат задачи.
     * @param int $id
     * @param array $fields
     * @return bool
     */
    public function updateTask(int $id, array $fields): bool;

    /**
     * Добавить запись к логам задачи.
     * @param int $id
     * @param string $logEntry
     * @return bool
     */
    public function appendTaskLog(int $id, string $logEntry): bool;

    /**
     * Удалить задачу.
     * @param int $id
     * @return bool
     */
    public function deleteTask(int $id): bool;
}
