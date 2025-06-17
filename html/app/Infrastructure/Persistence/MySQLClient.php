<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use PDO;
use GIG\Core\Config;
use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Domain\Exceptions\GeneralException;

/**
 * MySQLClient — адаптированный под современный стек, но с поддержкой auto-create DB и аккуратной генерацией DEFAULT
 */
class MySQLClient extends Database implements DatabaseClientInterface
{
    public function __construct(array $config = [])
    {
        $this->connect($config ?: $this->getDefaultConfig());
    }

    protected function connect(array $config): void
    {
        $host = $config['dbhost'] ?? 'localhost';
        $port = $config['dbport'] ?? 3306;
        $dbname = $config['dbname'] ?? '';
        $user = $config['dbuser'] ?? 'root';
        $pass = $config['dbpass'] ?? '';

        // Автоматическое создание БД (dev only — для prod можно убрать/закомментировать)
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (\PDOException $e) {
            throw new GeneralException('Ошибка создания базы данных', 500, [
                'detail' => $e->getMessage()
            ]);
        }

        // Подключаемся к целевой базе
        $dsnDb = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsnDb, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (\PDOException $e) {
            throw new GeneralException('Ошибка подключения к MySQL', 500, [
                'detail' => $e->getMessage()
            ]);
        }
    }

    protected function getDefaultConfig(): array
    {
        return Config::get('database') ?? [];
    }

    public function tableExists(string $table): bool
    {
        $this->validateIdentifier($table);
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->exec($sql, [$table]);
        // $stmt = $this->exec($sql, [$table], "Проверка существования таблицы $table");
        return (bool)$stmt->fetchColumn();
    }

    public function createTable(string $table, array $schema): bool
    {
        $this->validateIdentifier($table);

        $columns = [];
        $primaryKeys = [];
        $mulIndexes = [];

        foreach ($schema as $col) {
            $this->validateIdentifier($col['Field']);
            $line = "`{$col['Field']}` {$col['Type']}";
            if (($col['Null'] ?? 'YES') === 'NO') {
                $line .= " NOT NULL";
            }
            // DEFAULT
            if (array_key_exists('Default', $col) && $col['Default'] !== null) {
                $default = strtoupper((string)$col['Default']);
                if ($default === 'CURRENT_TIMESTAMP') {
                    $line .= " DEFAULT CURRENT_TIMESTAMP";
                } elseif (is_numeric($col['Default'])) {
                    $line .= " DEFAULT {$col['Default']}";
                } else {
                    $line .= " DEFAULT '" . addslashes($col['Default']) . "'";
                }
            }
            if (!empty($col['Extra'])) {
                $line .= " {$col['Extra']}";
            }
            $columns[] = $line;

            // PRIMARY KEY
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') {
                $primaryKeys[] = "`{$col['Field']}`";
            }
            // MUL (обычный индекс)
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'MUL') {
                $mulIndexes[] = $col['Field'];
            }
        }

        if ($primaryKeys) {
            $columns[] = "PRIMARY KEY (" . implode(',', $primaryKeys) . ")";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (\n" . implode(",\n", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->exec($sql, [], "Создание таблицы $table");

        // Создаем индексы для MUL
        foreach ($mulIndexes as $field) {
            $idxName = "idx_{$table}_{$field}";
            $idxSql = "CREATE INDEX `$idxName` ON `$table` (`$field`)";
            $this->exec($idxSql, [], "Создание индекса $idxName для $table");
        }

        return true;
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        $stmt = $this->exec("DESCRIBE `$table`", [], "DESCRIBE $table");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addColumn(string $table, array $column): void
    {
        $this->validateIdentifier($table);
        $name = $column['Field'];
        $type = $column['Type'];
        $null = ($column['Null'] ?? 'YES') === 'NO' ? 'NOT NULL' : 'NULL';

        // Обработка DEFAULT
        $default = '';
        if (array_key_exists('Default', $column) && $column['Default'] !== null) {
            $raw = strtoupper((string)$column['Default']);
            if ($raw === 'CURRENT_TIMESTAMP') {
                $default = "DEFAULT CURRENT_TIMESTAMP";
            } elseif (is_numeric($column['Default'])) {
                $default = "DEFAULT {$column['Default']}";
            } else {
                $default = "DEFAULT '" . addslashes($column['Default']) . "'";
            }
        }

        $extra = $column['Extra'] ?? '';

        $sql = "ALTER TABLE `$table` ADD COLUMN `$name` $type $null $default $extra";
        $this->exec($sql, [], "Добавление поля $name в $table");
    }

    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }
}
