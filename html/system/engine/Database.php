<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use PDO;
use PDOException;
use PDOStatement;
use GigReportServer\System\Exceptions\GeneralException;

abstract class Database implements DatabaseInterface
{
    protected PDO $pdo;
    abstract protected function connect(array $config): void;

    protected function validateIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new GeneralException("Недопустимое имя таблицы или поля", 400, [
                'detail' => "Идентификатор '$identifier' содержит недопустимые символы."
            ]);
        }
    }

    protected function buildSelect(string $table, array $fields, array $where = [], ?int $limit = null): array
    {
        $this->validateIdentifier($table);
        foreach ($fields as $field) {
            if ($field !== '*') $this->validateIdentifier($field);
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

    public function exec(string $sql, array $params = [], string $message = ''): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($message) new Event(Event::EVENT_INFO, self::class, $message);
            return $stmt;
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка выполнения запроса: {$e->getMessage()}", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function value(string $sql, array $params = [], string $message = ''): mixed
    {
        return $this->exec($sql, $params, $message)->fetchColumn();
    }

    public function get(string $table, array $where = [], array $fields = ['*']): array
    {
        [$sql, $params] = $this->buildSelect($table, $fields, $where);
        return $this->exec($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(string $table, array $where = [], array $fields = ['*']): ?array
    {
        [$sql, $params] = $this->buildSelect($table, $fields, $where, 1);
        return $this->exec($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

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
}
