<?php
namespace GigReportServer\System\Clients;

defined('_RUNKEY') or die;

use PDO;
use PDOException;
use GigReportServer\System\Exceptions\GeneralException;
use GigReportServer\System\Engine\Database;
use GigReportServer\System\Engine\Config;
use GigReportServer\System\Engine\Event;

class FirebirdClient extends Database 
{
    public function __construct(){
        $this->connect(Config::get('firebird'));
        new Event(Event::EVENT_INFO, self::class, 'Установлено соединение с базой данных Firebird');
    }

    protected function connect(array $config): void
    {
        $dsn = sprintf(
            'firebird:dbname=%s/%s:%s;charset=%s',
            $config['firebird_host'],
            $config['firebird_port'] ?? 3050,
            $config['firebird_dbpath'],
            $config['firebird_charset'] ?? 'UTF8'
        );

        try {
            $this->pdo = new PDO($dsn, $config['firebird_user'], $config['firebird_password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка соединения с базой данных Firebird", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function tableExists(string $table): bool
    {
        $this->validateIdentifier($table);
        $sql = 'SELECT 1 FROM RDB$RELATIONS WHERE RDB$RELATION_NAME = ?';
        return $this->value($sql, [strtoupper($table)]) !== false;
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        $sql = 'SELECT RDB$FIELD_NAME AS Field FROM RDB$RELATION_FIELDS WHERE RDB$RELATION_NAME = ? ORDER BY RDB$FIELD_POSITION';
        return $this->exec($sql, [strtoupper($table)])->fetchAll(PDO::FETCH_ASSOC);
    }
}
