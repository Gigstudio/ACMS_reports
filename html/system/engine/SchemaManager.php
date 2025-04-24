<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;
use GigReportServer\System\Clients\MySQLClient;

class SchemaManager
{
    private MySQLClient $db;
    private string $schemaPath;
    private array $requiredTables = ['users'];
    private string $version;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->schemaPath = PATH_CONFIG . 'dbschema/';
        $this->version = Config::get('database.dbversion', 'm00000');
    }

    public function checkAndMigrate(){
        $this->ensureSettingsTable();
        $this->ensureMigrationsTable();

        $current = $this->getCurrentVersion();
        if($current !== $this->version){
            $this->applyMigration();
            $this->updateVersion();
        }
    }

    private function getCurrentVersion(): string
    {
        if (!$this->db->tableExists('db_settings')) return '';
        return $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']);
    }

    private function updateVersion(){
        $value = $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']);
        if ($value === false) {
            trigger_error('Превый запуск!', E_USER_WARNING);
            $this->db->insert('db_settings', [
                'param' => 'dbversion',
                'value' => $this->version
            ]);
        } elseif ($value !== $this->version) {
            trigger_error("Версия базы данных обновлена с {$value} до {$this->version}", E_USER_WARNING);
            $this->db->update('db_settings', ['value' => $this->version], ['param' => 'dbversion']);
        }        $affected = $this->db->update('db_settings', ['value' => $this->version], ['param' => 'dbversion']);
    }

    private function ensureSettingsTable(): void
    {
        if (!$this->db->tableExists('db_settings')) {
            $this->db->createTable('db_settings', [
                ['Field' => 'param', 'Type' => 'varchar(64)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => 'PRI'],
                ['Field' => 'value', 'Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            new Event(Event::EVENT_INFO, self::class, "Создана таблица db_settings");
        }
    }

    private function ensureMigrationsTable(): void
    {
        if (!$this->db->tableExists('db_migrations')) {
            $this->db->createTable('db_migrations', [
                ['Field' => 'id', 'Type' => 'int(11)', 'Null' => 'NO', 'Default' => null, 'Extra' => 'auto_increment', 'Key' => 'PRI'],
                ['Field' => 'version', 'Type' => 'varchar(32)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => ''],
                ['Field' => 'applied_at', 'Type' => 'timestamp', 'Null' => 'NO', 'Default' => 'CURRENT_TIMESTAMP', 'Extra' => '', 'Key' => ''],
                ['Field' => 'description', 'Type' => 'text', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            new Event(Event::EVENT_INFO, self::class, "Создана таблица db_migrations");
        }
    }

    public function applyMigration(): void{
        foreach ($this->requiredTables as $table) {
            if (!$this->db->tableExists($table)) {
                $file = $this->schemaPath . $table . '.json';

                if (!file_exists($file)) {
                    throw new GeneralException("Не найден файл схемы", 500, [
                        'detail' => "Файл $file не найден."
                    ]);
                }

                $schema = json_decode(file_get_contents($file), true);
                if (!is_array($schema)) {
                    throw new GeneralException("Ошибка чтения схемы", 500, [
                        'detail' => "Файл $file содержит некорректный JSON."
                    ]);
                }

                $this->db->createTable($table, $schema);
                new Event(Event::EVENT_INFO, self::class, "Создана таблица $table");
            }
        }
    }
}
