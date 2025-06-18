<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use PDO;
use PDOStatement;
use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;

/**
 * Абстрактный универсальный клиент для PDO-совместимых БД.
 * Потомки должны реализовать метод connect().
 */
abstract class Database implements DatabaseClientInterface
{
    protected PDO $pdo;

    /**
     * Потомки ОБЯЗАНЫ реализовать метод подключения.
     */
    abstract protected function connect(array $config): void;

    // --- Проверка идентификаторов ---
    protected function validateIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new GeneralException("Недопустимое имя таблицы или поля", 400, [
                'detail' => "Идентификатор '$identifier' содержит недопустимые символы."
            ]);
        }
    }

    // --- Универсальный конструктор выборки SELECT ---
    protected function buildSelect(string $table, array $fields, array $where = [], ?int $limit = null): array
    {
        $this->validateIdentifier($table);
        foreach ($fields as $field) {
            if ($field !== '*') {
                $this->validateIdentifier($field);
            }
        }

        $sql = "SELECT " . implode(", ", $fields) . " FROM `$table`";
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $val) {
                $this->validateIdentifier($key);
                $conditions[] = "`$key` = ?";
                $params[] = $val;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }

        return [$sql, $params];
    }

    // --- Базовые методы выполнения SQL ---

    public function exec(string $sql, array $params = [], string $message = ''): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($message) {
                EventManager::logParams(Event::INFO, static::class, $message);
            }
            return $stmt;
        } catch (\PDOException $e) {
            throw new GeneralException("Ошибка выполнения запроса", 500, [
                'detail' => $e->getMessage(),
                'sql'    => $sql,
                'params' => $params
            ]);
        }
    }

    public function value(string $sql, array $params = [], string $message = ''): mixed
    {
        return $this->exec($sql, $params, $message)->fetchColumn();
    }

    // --- CRUD-операции ---

    public function get(string $table, array $where = [], array $fields = ['*'], int $limit = null): array
    {
        [$sql, $params] = $this->buildSelect($table, $fields, $where, $limit);
        return $this->exec($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(string $table, array $where = [], array $fields = ['*']): ?array
    {
        [$sql, $params] = $this->buildSelect($table, $fields, $where, 1);
        return $this->exec($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insert(string $table, array $data): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($data) as $field) {
            $this->validateIdentifier($field);
        }

        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES ($placeholders)";
        $params = array_values($data);

        return $this->exec($sql, $params, $table === "log" ? "" : "Добавление записи в $table")->rowCount() > 0;
    }

    public function massInsert(string $table, array $fields, array $rows): int
    {
        $this->validateIdentifier($table);
        foreach ($fields as $field) {
            $this->validateIdentifier($field);
        }

        if (empty($rows)) return 0;

        $placeholders = '(' . implode(',', array_fill(0, count($fields), '?')) . ')';
        $allPlaceholders = implode(',', array_fill(0, count($rows), $placeholders));
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $fields) . "`) VALUES $allPlaceholders";
        $params = [];
        foreach ($rows as $row) {
            // Приводим к нужному порядку, если строки ассоциативные:
            if (array_keys($row) !== range(0, count($row)-1)) {
                $ordered = [];
                foreach ($fields as $f) $ordered[] = $row[$f] ?? null;
                $params = array_merge($params, $ordered);
            } else {
                $params = array_merge($params, $row);
            }
        }
        $stmt = $this->exec($sql, $params, "Массовая вставка в $table");
        return $stmt->rowCount();
    }

    public function update(string $table, array $data, array $where): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($data) as $field) {
            $this->validateIdentifier($field);
        }
        foreach (array_keys($where) as $key) {
            $this->validateIdentifier($key);
        }

        $set = [];
        $params = [];
        foreach ($data as $key => $val) {
            $set[] = "`$key` = ?";
            $params[] = $val;
        }
        $sql = "UPDATE `$table` SET " . implode(", ", $set);

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $val) {
                $conditions[] = "`$key` = ?";
                $params[] = $val;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        return $this->exec($sql, $params, "Обновление записи в $table")->rowCount() > 0;
    }

    public function updateOrInsert(string $table, array $data, array $where): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($data) as $field) {
            $this->validateIdentifier($field);
        }
        foreach (array_keys($where) as $key) {
            $this->validateIdentifier($key);
        }

        $found = $this->first($table, $where);
        if ($found) {
            return $this->update($table, $data, $where);
        } else {
            $insert = array_merge($where, $data);
            return $this->insert($table, $insert);
        }
    }

    public function delete(string $table, array $where): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($where) as $key) {
            $this->validateIdentifier($key);
        }

        if (empty($where)) {
            throw new GeneralException("Удаление без условий запрещено", 400, [
                'detail' => "Для предотвращения случайного удаления, условия обязательны."
            ]);
        }

        $conditions = [];
        $params = [];
        foreach ($where as $key => $val) {
            $conditions[] = "`$key` = ?";
            $params[] = $val;
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(" AND ", $conditions);

        return $this->exec($sql, $params, "Удаление записи из $table")->rowCount() > 0;
    }

    // --- DDL, транзакции и сервисные методы ---

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function truncate(string $table): void
    {
        $this->validateIdentifier($table);
        $this->exec("TRUNCATE TABLE `$table`", [], "Очистка таблицы $table");
    }

    public function dropTable(string $table): void
    {
        $this->validateIdentifier($table);
        $this->exec("DROP TABLE IF EXISTS `$table`", [], "Удаление таблицы $table");
    }

    // --- Методы, которые требуют реализации в потомках ---
    // (если возможно — реализуй дефолтно для PDO)

    abstract public function tableExists(string $table): bool;
    abstract public function createTable(string $table, array $schema): bool;
    abstract public function describeTable(string $table): array;
    abstract public function addColumn(string $table, array $column): void;
    abstract public function lastInsertId(): int;
}
