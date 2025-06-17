<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use PDO;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use PDOException;
use GIG\Core\Config;

class FirebirdClient extends Database implements DatabaseClientInterface
{
    public function __construct(array $config = [])
    {
        $this->connect($config ?: $this->getDefaultConfig());
    }

    protected function connect(array $config): void
    {
        $host = $config['firebird_host'] ?? 'localhost';
        $port = $config['firebird_port'] ?? 3050;
        $db = $config['firebird_dbpath'] ?? '';
        $charset = $config['firebird_charset'] ?? 'UTF8';
        $user = $config['firebird_user'] ?? 'SYSDBA';
        $pass = $config['firebird_password'] ?? '';

        $dsn = "firebird:dbname={$host}/{$port}:{$db};charset={$charset}";
        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка подключения к FireBird", 500, [
                'detail' => $e->getMessage(),
                'dsn' => $dsn
            ]);
        }
    }

    protected function getDefaultConfig(): array
    {
        return Config::get('database') ?? [];
    }

    /**
     * Проверяет существование таблицы.
     */
    public function tableExists(string $table): bool
    {
        $this->validateIdentifier($table);
        $sql = "SELECT 1 FROM rdb\$relations WHERE rdb\$relation_name = ?";
        $name = strtoupper($table);
        return (bool)$this->value($sql, [$name]);
    }

    /**
     * Создать таблицу по простой схеме (массив вида [field => type, ...])
     */
    public function createTable(string $table, array $schema): bool
    {
        $this->validateIdentifier($table);
        $columns = [];
        foreach ($schema as $col => $type) {
            $this->validateIdentifier($col);
            $columns[] = "\"$col\" $type";
        }
        $sql = "CREATE TABLE \"$table\" (" . implode(", ", $columns) . ")";
        $this->exec($sql);
        return $this->tableExists($table);
    }

    /**
     * Добавить колонку в таблицу.
     */
    public function addColumn(string $table, array $column): void
    {
        $this->validateIdentifier($table);
        $name = $column['name'] ?? $column[0] ?? null;
        $type = $column['type'] ?? $column[1] ?? null;
        if (!$name || !$type) {
            throw new GeneralException("Не заданы имя или тип для колонки", 400, [
                'detail' => "Файл $table.json содержит некорректные данные."
            ]);
        }
        $this->validateIdentifier($name);
        $sql = "ALTER TABLE \"$table\" ADD \"$name\" $type";
        $this->exec($sql);
    }

    /**
     * Вернуть ID последней вставленной записи.
     * В Firebird это работает ТОЛЬКО при использовании GENERATOR/SEQUENCE+TRIGGER,
     * и нужно явно указывать имя sequence!
     * Для типовых таблиц — используй вручную.
     */
    public function lastInsertId(): int
    {
        // Для универсальности ожидаем наличие sequence по имени <table>_SEQ
        // Пример: для таблицы users => SEQUENCE USERS_SEQ
        // $this->pdo->lastInsertId() требует имя sequence!
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * TRUNCATE — в Firebird нет, используем DELETE FROM
     */
    public function truncate(string $table): void
    {
        $this->validateIdentifier($table);
        $this->exec("DELETE FROM \"$table\"");
    }

    /**
     * DROP TABLE
     */
    public function dropTable(string $table): void
    {
        $this->validateIdentifier($table);
        $this->exec("DROP TABLE \"$table\"");
    }

    // --- LIMIT/ROWS override ---

    /**
     * Firebird: LIMIT/ROWS в buildSelect (переопределяем)
     */
    protected function buildSelect(string $table, array $fields, array $where = [], ?int $limit = null): array
    {
        $this->validateIdentifier($table);
        foreach ($fields as $field) {
            if ($field !== '*') {
                $this->validateIdentifier($field);
            }
        }

        $sql = "SELECT " . implode(", ", $fields) . " FROM \"$table\"";
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $val) {
                $this->validateIdentifier($key);
                $conditions[] = "\"$key\" = ?";
                $params[] = $val;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        if ($limit !== null) {
            // В Firebird: ROWS 1
            $sql .= " ROWS 1";
        }

        return [$sql, $params];
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        $name = strtoupper($table);
        $sql = <<<SQL
SELECT
    rf.rdb\$field_name AS field,
    f.rdb\$field_type AS field_type,
    f.rdb\$field_sub_type AS field_sub_type,
    f.rdb\$field_length AS field_length,
    f.rdb\$field_precision AS field_precision,
    f.rdb\$field_scale AS field_scale,
    f.rdb\$character_length AS char_length,
    rf.rdb\$null_flag AS not_null,
    rf.rdb\$default_source AS default_source,
    rf.rdb\$description AS description
FROM
    rdb\$relation_fields rf
LEFT JOIN
    rdb\$fields f ON rf.rdb\$field_source = f.rdb\$field_name
WHERE
    rf.rdb\$relation_name = ?
ORDER BY
    rf.rdb\$field_position
SQL;

        $fields = $this->exec($sql, [$name])->fetchAll();

        $result = [];
        foreach ($fields as $f) {
            $type = $this->firebirdTypeToString(
                $f['FIELD_TYPE'],
                $f['FIELD_SUB_TYPE'],
                $f['FIELD_LENGTH'],
                $f['FIELD_PRECISION'],
                $f['FIELD_SCALE'],
                $f['CHAR_LENGTH']
            );
            $result[] = [
                'Field'   => trim($f['FIELD']),
                'Type'    => $type,
                'Null'    => ($f['NOT_NULL'] ? 'NO' : 'YES'),
                'Default' => $this->parseDefault($f['DEFAULT_SOURCE']),
                'Extra'   => trim($f['DESCRIPTION'] ?? ''),
                // Можешь добавить тут дополнительные поля по желанию
            ];
        }
        return $result;
    }

    /**
     * Преобразовать Firebird TYPE в строку.
     */
    protected function firebirdTypeToString($type, $subtype, $length, $precision, $scale, $charlen)
    {
        static $types = [
            7   => 'SMALLINT',
            8   => 'INTEGER',
            9   => 'QUAD',
            10  => 'FLOAT',
            12  => 'DATE',
            13  => 'TIME',
            14  => 'CHAR',
            16  => 'BIGINT',
            27  => 'DOUBLE',
            35  => 'TIMESTAMP',
            37  => 'VARCHAR',
            40  => 'CSTRING',
            261 => 'BLOB'
        ];
        $base = $types[$type] ?? 'UNKNOWN';

        // VARCHAR, CHAR
        if (in_array($base, ['VARCHAR', 'CHAR', 'CSTRING'])) {
            $len = (int)$charlen ?: (int)$length;
            return $base . "($len)";
        }
        // NUMERIC/DECIMAL
        if ($base === 'BIGINT' && $subtype > 0) {
            return ($subtype === 1 ? 'NUMERIC' : 'DECIMAL') . "($precision, $scale)";
        }
        return $base;
    }

    /**
     * Получить значение по умолчанию (строку) из default_source.
     */
    protected function parseDefault($default)
    {
        if (!$default) return null;
        if (preg_match('/DEFAULT\s+(.+)/i', $default, $m)) {
            return trim($m[1], " '\"");
        }
        return trim($default, " '\"");
    }
}
