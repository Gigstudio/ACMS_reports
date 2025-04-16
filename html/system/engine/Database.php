<?php
namespace GigReportServer\System\Engine;

use PDO;
use PDOException;
use GigReportServer\System\Exceptions\GeneralException;
use PDOStatement;

defined('_RUNKEY') or die;

class Database
{
    protected PDO $pdo;

    public function __construct(array $config){
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['dbhost'],
            $config['dbport'] ?? 3306,
            $config['dbname']
        );
        try{
            $this->pdo = new PDO($dsn, $config['dbuser'], $config['dbpass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            new Event(Event::EVENT_INFO, self::class, 'Установлено соединение с базой данных.');
        }catch(PDOException $e){
            throw new GeneralException("Ошибка соединения с базой данных MySQL", 500, [
                'detail' => "При инициализации соединения возникла проблема подключения. Проверьте данные для подключения, файл init.conf",
            ]);
        }
    }

    protected function prepareAndExecute(string $sql, array $params = [], string $message = ''): bool|PDOStatement{
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($message){
            new Event(Event::EVENT_INFO, self::class, $message);
        }
        return $stmt;
    }

    public function exec(string $sql, array $params = []): bool|int{
        if(!empty($params && stripos($sql, '?') === false)){
            new Event(Event::EVENT_WARNING, self::class, 'Попытка выполнить параметризованный запрос без плейсхолдеров.');
        }
        try{
            if(empty($params)){
                $count = $this->pdo->exec($sql);
                new Event(Event::EVENT_INFO, self::class, "Выполнен SQL-запрос (exec): $sql");
                return $count;
            }
            $stmt = $this->prepareAndExecute($sql, $params, "Выполнен SQL-запрос с параметрами: $sql");
            return $stmt->rowCount();
        }catch(PDOException $e){
            throw new GeneralException("Ошибка выполнения SQL-запроса", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function fetchOne(string $sql, array $params = []): mixed{
        try{
            $stmt = $this->prepareAndExecute($sql, $params, "Выполнен fetchOne-запрос: $sql");
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка получения одной строки", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->prepareAndExecute($sql, $params, "Выполнен fetchAll-запрос: $sql");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка получения данных из таблицы", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        try {
            $stmt = $this->prepareAndExecute($sql, $params, "Выполнен fetchColumn-запрос: $sql");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new GeneralException("Ошибка получения значения столбца", 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function lastInsertId(): string
    {
        $id = $this->pdo->lastInsertId();
        new Event(Event::EVENT_INFO, self::class, 'Последний insertId: ' . $id);
        return $id;
    }

    public function getVersion(): string
    {
        $version = preg_replace('/[^0-9.].*/', '', $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        new Event(Event::EVENT_INFO, self::class, 'Версия сервера MySQL: ' . $version);
        return $version;
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}