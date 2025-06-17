<?php
namespace GIG\Infrastructure\Repository;

use GIG\Domain\Entities\Event;
use GIG\Infrastructure\Persistence\MySQLClient;

defined('_RUNKEY') or die;

/**
 * Репозиторий событий (log)
 */
class EventRepository
{
    protected MySQLClient $db;

    public function __construct(MySQLClient $db)
    {
        $this->db = $db;
    }

    /**
     * Сохраняет событие в лог (insert)
     */
    public function save(Event $event): int
    {
        $data = [
            'type'      => $event->type,
            'source'    => $event->source,
            'message'   => $event->message,
            'timestamp' => $event->timestamp ? date('Y-m-d H:i:s', $event->timestamp) : date('Y-m-d H:i:s'),
            'data'      => $event->data ? json_encode($event->data, JSON_UNESCAPED_UNICODE) : null,
            'user_id'   => $event->data['user_id'] ?? null,
            'ip'        => $event->data['ip'] ?? null,
        ];
        $this->db->insert('log', $data);
        return $this->db->lastInsertId();
    }

        /**
         * Найти событие по id
         */
    public function findById(int $id): ?Event
    {
        $row = $this->db->first('log', ['*'], ['id' => $id]);
        if (!$row) {
            return null;
        }
        return $this->rowToEvent($row);
    }

    /**
     * Получить все события (опционально с лимитом и фильтром)
     */
    public function findAll(array $filter = []): array
    {
        $rows = $this->db->get('log', $filter);
        return array_map([$this, 'rowToEvent'], $rows);
    }
    
    /**
     * Преобразует строку БД в сущность Event
     */
    protected function rowToEvent(array $row): Event
    {
        // Если timestamp — datetime, преобразуем в int
        $row['timestamp'] = is_string($row['timestamp']) ? strtotime($row['timestamp']) : $row['timestamp'];
        // Декодируем data, если есть
        if (isset($row['data']) && $row['data']) {
            $row['data'] = json_decode($row['data'], true);
        } else {
            $row['data'] = [];
        }
        // user_id и ip — в data (или можно добавить отдельные поля в Event, если потребуется)
        if (isset($row['user_id'])) {
            $row['data']['user_id'] = $row['user_id'];
        }
        if (isset($row['ip'])) {
            $row['data']['ip'] = $row['ip'];
        }
        return new Event($row);
    }
}
