<?php
namespace GigReportServer\System\Clients;

defined('_RUNKEY') or die;

use PDO;
use PDOException;
use GigReportServer\System\Engine\Database;
use GigReportServer\System\Engine\Event;
use GigReportServer\System\Engine\Config;
use GigReportServer\System\Exceptions\GeneralException;

class MySQLClient extends Database
{
    public function __construct()
    {
        $this->connect(Config::get('database'));
        // new Event(Event::EVENT_INFO, self::class, 'Установлено соединение с базой данных MySQL');
    }

    protected function connect(array $config): void {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['dbhost'],
            $config['dbport'] ?? 3306,
            $config['dbname']
        );

        try {
            $this->pdo = new PDO($dsn, $config['dbuser'], $config['dbpass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка соединения с базой данных MySQL", 500, [
                'detail' => "При инициализации соединения возникла проблема подключения. Проверьте данные для подключения, файл init.json",
            ]);
        }
    }

    public function insert(string $table, array $data): bool {
        $this->validateIdentifier($table);
        foreach (array_keys($data) as $field) {
            $this->validateIdentifier($field);
        }

        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES ($placeholders)";
        $params = array_values($data);

        return $this->exec($sql, $params, "MySQL: Добавление записи в $table")->rowCount() > 0;
    }

    public function update(string $table, array $data, array $where): bool {
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

        return $this->exec($sql, $params, "MySQL: Обновление записи в $table")->rowCount() > 0;
    }

    public function delete(string $table, array $where): bool
    {
        $this->validateIdentifier($table);
        foreach (array_keys($where) as $key) {
            $this->validateIdentifier($key);
        }

        if (empty($where)) {
            throw new GeneralException("MySQL: Удаление без условий запрещено", 400, [
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

        return $this->exec($sql, $params, "MySQL: Удаление записи из $table")->rowCount() > 0;
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
        // trigger_error($sql, E_USER_NOTICE);
        $this->exec($sql, [], "MySQL: Создание таблицы $table");
        return true;
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        return $this->exec("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    }
}