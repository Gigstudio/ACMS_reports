<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Domain\Entities\Event;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Persistence\MySQLClient;

class EventRepository
{
    protected MySQLClient $db;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->columns = $this->getColumnNames('log');
    }

    private function getColumnNames(string $table): array
    {
        $result = $this->db->describeTable($table);
        return array_column($result, 'Field');
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
            'timestamp' => date('Y-m-d H:i:s', $event->timestamp),
            'data'      => $event->data ? json_encode($event->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
            'user_id'   => $event->userId,
            'ip'        => $event->ip,
        ];

        $this->validateFields($data);
        $this->db->insert('log', $data);

        return $this->db->lastInsertId();
    }

    /**
     * Найти событие по id
     */
    public function findById(int $id): ?Event
    {
        $row = $this->db->first('log', ['id' => $id]);
        return $row ? $this->rowToEvent($row) : null;
    }

    /**
     * Получить все события (опционально с лимитом и фильтром)
     */
    public function findAll(array $filter = [], int $limit = 100): array
    {
        $rows = $this->db->get('log', $filter, ['*'], $limit);
        return array_map([$this, 'rowToEvent'], $rows);
    }

    public function getLastId()
    {
        return (int) $this->db->value("SELECT MAX(id) FROM log");
    }

    public function getLastMessage(): Event|null{
        return $this->findById($this->getLastId());
    }

    public function count()
    {
        return $this->db->count('log');
    }

    /**
     * Преобразует строку БД в сущность Event
     */
    protected function rowToEvent(array $row): Event
    {
        return new Event([
            'id'        => $row['id'] ?? null,
            'type'      => (int) $row['type'],
            'source'    => $row['source'],
            'message'   => $row['message'],
            'timestamp' => is_string($row['timestamp']) ? strtotime($row['timestamp']) : (int) $row['timestamp'],
            'ip'        => $row['ip'] ?? '127.0.0.1',
            'userId'    => $row['user_id'] ?? null,
            'data'      => isset($row['data']) ? json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR) : [],
        ]);
    }

    private function validateFields(array $data): void
    {
        foreach ($data as $key => $_) {
            if (!in_array($key, $this->columns, true)) {
                throw new GeneralException("Недопустимое поле '$key' при сохранении события.", 500, [
                    'detail' => "Поле '$key' не является полем таблицы log"
                ]);
            }
        }
    }
}
