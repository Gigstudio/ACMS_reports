<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;
use GIG\Core\Config;
use GIG\Domain\Exceptions\GeneralException;

/**
 * Управляет версионностью и схемой базы данных.
 * Отвечает за автоматическую проверку, создание и миграцию структуры.
 */
class DbSchemaManager
{
    protected DatabaseClientInterface $db;
    protected string $schemaPath;
    protected string $version;

    /**
     * @param DatabaseClientInterface $db  Активное соединение с БД.
     * @param string|null $schemaPath      Путь к папке схем. Если не указан — берётся из PATH_CONFIG.
     */
    public function __construct(DatabaseClientInterface $db, ?string $schemaPath = null)
    {
        $this->db = $db;
        $this->schemaPath = $schemaPath ?? PATH_CONFIG . 'dbschema' . DS;
        $this->version = Config::get('database.dbversion', 'm00000');
    }

    /**
     * Главный публичный метод: проверить и, если нужно, выполнить миграцию БД.
     */
    public function checkAndMigrate(): void
    {
        $this->ensureSettingsTable();
        $this->ensureMigrationsTable();
        $this->ensureRolesTable();
        $this->ensureBackgroundTasksTable();

        $current = $this->getCurrentVersion();
        if ($current !== $this->version) {
            $this->applyMigration();
            $this->updateVersion();
        }
    }

    /**
     * Получить актуальную версию схемы из таблицы db_settings.
     */
    protected function getCurrentVersion(): string
    {
        if (!$this->db->tableExists('db_settings')) {
            return '';
        }
        return $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']) ?? '';
    }

    /**
     * Обновить версию схемы в таблице db_settings.
     */
    protected function updateVersion(): void
    {
        $value = $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']);
        if ($value === false) {
            EventManager::logParams(Event::WARNING, self::class, 'Первый запуск: инициализация версии базы');
            $this->db->insert('db_settings', [
                'param' => 'dbversion',
                'value' => $this->version
            ]);
        } elseif ($value !== $this->version) {
            EventManager::logParams(Event::INFO, self::class, "Версия базы данных обновлена с {$value} до {$this->version}");
            $this->db->updateOrInsert('db_settings', ['value' => $this->version], ['param' => 'dbversion']);
        }
    }

    /**
     * Убедиться, что существует таблица db_settings.
     */
    protected function ensureSettingsTable(): void
    {
        if (!$this->db->tableExists('db_settings')) {
            $this->db->createTable('db_settings', [
                ['Field' => 'param', 'Type' => 'varchar(64)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => 'PRI'],
                ['Field' => 'value', 'Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            EventManager::logParams(Event::INFO, self::class, "Создана таблица db_settings");
        }
    }

    /**
     * Убедиться, что существует таблица db_migrations.
     */
    protected function ensureMigrationsTable(): void
    {
        if (!$this->db->tableExists('db_migrations')) {
            $this->db->createTable('db_migrations', [
                ['Field' => 'id', 'Type' => 'int', 'Null' => 'NO', 'Default' => null, 'Extra' => 'auto_increment', 'Key' => 'PRI'],
                ['Field' => 'version', 'Type' => 'varchar(32)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => ''],
                ['Field' => 'applied_at', 'Type' => 'timestamp', 'Null' => 'NO', 'Default' => 'CURRENT_TIMESTAMP', 'Extra' => '', 'Key' => ''],
                ['Field' => 'description', 'Type' => 'text', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            EventManager::logParams(Event::INFO, self::class, "Создана таблица db_migrations");
        }
    }

    /**
     * Убедиться, что существует таблица ролей, и наполнить её начальными данными.
     */
    protected function ensureRolesTable(): void
    {
        if (!$this->db->tableExists('roles')) {
            $this->db->createTable('roles', [
                ['Field' => 'id', 'Type' => 'int', 'Null' => 'NO', 'Default' => null, 'Extra' => 'AUTO_INCREMENT', 'Key' => 'PRI'],
                ['Field' => 'name', 'Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => ''],
                ['Field' => 'description', 'Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            EventManager::logParams(Event::INFO, self::class, "Создана таблица roles");
        }
        $this->ensureDefaultRoles();
    }

    /**
     * Добавляет базовые роли, если их нет.
     */
    protected function ensureDefaultRoles(): void
    {
        $roleList = [
            ['name' => 'Common',  'description' => 'Все пользователи'],
            ['name' => 'Table',   'description' => 'Оператор/табельщик'],
            ['name' => 'Lead',    'description' => 'Руководитель подразделения'],
            ['name' => 'Control', 'description' => 'Служба контроля'],
            ['name' => 'Admin',   'description' => 'Администратор системы']
        ];
        foreach ($roleList as $role) {
            $exists = $this->db->value("SELECT id FROM roles WHERE name = ?", [$role['name']]);
            if (!$exists) {
                $this->db->insert('roles', $role);
                EventManager::logParams(Event::INFO, self::class, "Добавлена роль {$role['name']}");
            }
        }
    }

    protected function ensureBackgroundTasksTable()
    {
        if (!$this->db->tableExists('background_tasks')) {
            $this->db->createTable('background_tasks', [
                ["Field" => "id", "Type" => "int", "Null" => "NO", "Default" => null, "Extra" => "AUTO_INCREMENT", "Key" => "PRI"],
                ["Field" => "type", "Type" => "varchar(64)", "Null" => "NO", "Default" => "", "Extra" => "", "Key" => ""],
                ["Field" => "status", "Type" => "ENUM('pending','running','done','error','aborted')", "Null" => "NO", "Default" => "pending", "Extra" => "", "Key" => ""],
                ["Field" => "params", "Type" => "json", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""],
                ["Field" => "progress", "Type" => "float", "Null" => "NO", "Default" => 0, "Extra" => "", "Key" => ""],
                ["Field" => "result", "Type" => "json", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""],
                ["Field" => "log", "Type" => "json", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""],
                ["Field" => "user_id", "Type" => "int", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""],
                ["Field" => "created_at", "Type" => "timestamp", "Null" => "NO", "Default" => "CURRENT_TIMESTAMP", "Extra" => "", "Key" => ""],
                ["Field" => "updated_at", "Type" => "timestamp", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""],
                ["Field" => "finished_at", "Type" => "timestamp", "Null" => "YES", "Default" => null, "Extra" => "", "Key" => ""]
            ]);
            EventManager::logParams(Event::INFO, self::class, "Создана таблица background_tasks");
        }
    }

    /**
     * Применяет миграции ко всем таблицам по json-схемам из папки dbschema.
     */
    public function applyMigration(): void
    {
        $files = scandir($this->schemaPath);
        if (!$files) {
            throw new GeneralException("Не найдены схемы миграции!", 500, [
                'detail' => "Папка схем пуста: $this->schemaPath"
            ]);
        }
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'json') continue;

            $table = pathinfo($file, PATHINFO_FILENAME);
            $schemaFile = $this->schemaPath . $file;

            $schema = json_decode(file_get_contents($schemaFile), true);
            if (!is_array($schema)) {
                EventManager::logParams(Event::ERROR, self::class, "Ошибка чтения схемы $file: некорректный JSON", [
                    'detail' => "Файл $file содержит некорректный JSON."
                ]);
                continue;
            }

            if (!$this->db->tableExists($table)) {
                $this->db->createTable($table, $schema);
                EventManager::logParams(Event::INFO, self::class, "Создана таблица $table");
                continue;
            }

            // Сравнение и добавление недостающих полей
            $existing = $this->db->describeTable($table);
            $existingFields = array_column($existing, 'Field');
            foreach ($schema as $column) {
                if (!in_array($column['Field'], $existingFields, true)) {
                    $this->db->addColumn($table, $column);
                    EventManager::logParams(Event::INFO, self::class, "Добавлено поле {$column['Field']} в таблицу $table");
                }
            }
        }
    }

    /**
     * Проверяет структуру, а при необходимости мигрирует БД одним статическим вызовом.
     * Для простоты и обратной совместимости.
     */
    public static function checkAndMigrateStatic(DatabaseClientInterface $db, ?string $schemaPath = null): void
    {
        $mgr = new self($db, $schemaPath);
        $mgr->checkAndMigrate();
    }
}
