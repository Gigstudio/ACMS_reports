<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use PDO;
use PDOException;
use PDOStatement;
use GigReportServer\System\Exceptions\GeneralException;

class Database
{
    protected PDO $pdo;

    public function __construct()
    {
        $config = Config::get('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['dbhost'],
            $config['dbport'] ?? 3306,
            $config['dbname']
        );

        try {
            $this->pdo = new PDO($dsn, $config['dbuser'], $config['dbpass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            new Event(Event::EVENT_INFO, self::class, 'Установлено соединение с локальной базой данных');
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка соединения с локальной базой данных", 500, [
                'detail' => "При инициализации соединения возникла проблема подключения. Проверьте данные для подключения к MySQL, файл init.json",
            ]);
        }
    }

    private function validateIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new GeneralException("Локальная БД: недопустимое имя таблицы или поля", 400, [
                'detail' => "Идентификатор '$identifier' содержит недопустимые символы."
            ]);
        }
    }

    private function buildSelect(string $table, array $fields, array $where = [], ?int $limit = null): array
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
            throw new GeneralException("Локальная БД: ошибка выполнения запроса: {$e->getMessage()}", 500, [
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

        return $this->exec($sql, $params, "Локальная БД: добавление записи в $table")->rowCount() > 0;
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

        return $this->exec($sql, $params, "Локальная БД: обновление записи в $table")->rowCount();
    }

    public function delete(string $table, array $where): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($where) as $key) {
            $this->validateIdentifier($key);
        }

        if (empty($where)) {
            throw new GeneralException("Локальная БД: удаление без условий запрещено", 400, [
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

        return $this->exec($sql, $params, "Локальная БД: удаление записи из $table")->rowCount() > 0;
    }

    public function tableExists(string $table): bool
    {
        $this->validateIdentifier($table);
        $sql = "SHOW TABLES LIKE ?";
        return $this->value($sql, [$table]) !== false;
    }

    public function createTable(string $table, array $schema): bool
    {
        $this->validateIdentifier($table);
        $columns = [];
        $primaryKey = null;

        foreach ($schema as $column) {
            $this->validateIdentifier($column['Field']);
            $line = "`{$column['Field']}` {$column['Type']}";
            if ($column['Null'] === 'NO') {
                $line .= " NOT NULL";
            }
            if ($column['Default'] !== null) {
                $default = in_array(strtoupper($column['Default']), ['CURRENT_TIMESTAMP']) ? $column['Default'] : (is_numeric($column['Default']) ? $column['Default'] : "'{$column['Default']}'");
                $line .= " DEFAULT $default";
            }
            if (!empty($column['Extra'])) {
                $line .= " {$column['Extra']}";
            }
            $columns[] = $line;

            if (isset($column['Key']) && strtoupper($column['Key']) === 'PRI') {
                $primaryKey = $column['Field'];
            }
        }

        if ($primaryKey) {
            $columns[] = "PRIMARY KEY (`$primaryKey`)";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(", ", $columns) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->exec($sql, [], "Локальная БД: создание таблицы $table");
        return true;
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
        $this->exec("TRUNCATE TABLE `$table`", [], "Локальная БД: очистка таблицы $table");
    }

    public function dropTable(string $table): void
    {
        $this->validateIdentifier($table);
        $this->exec("DROP TABLE IF EXISTS `$table`", [], "Локальная БД: удаление таблицы $table");
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        return $this->exec("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    }
}
