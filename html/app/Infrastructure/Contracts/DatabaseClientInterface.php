<?php
namespace GIG\Infrastructure\Contracts;

defined('_RUNKEY') or die;

/**
 * Универсальный интерфейс клиента работы с БД.
 */
interface DatabaseClientInterface
{
    // Основные низкоуровневые методы
    public function exec(string $sql, array $params = [], string $message = ''): \PDOStatement;
    public function value(string $sql, array $params = [], string $message = ''): mixed;

    // CRUD-запросы
    public function get(string $table, array $where = [], array $fields = ['*']): array;
    public function first(string $table, array $where = [], array $fields = ['*']): ?array;
    public function insert(string $table, array $data): bool;
    public function massInsert(string $table, array $fields, array $rows): int;
    public function update(string $table, array $data, array $where): bool;
    public function updateOrInsert(string $table, array $data, array $where): bool;
    public function delete(string $table, array $where): bool;
    public function lastInsertId(): int;

    // Инфраструктура таблиц
    public function tableExists(string $table): bool;
    public function createTable(string $table, array $schema): bool;
    public function describeTable(string $table): array;
    public function addColumn(string $table, array $column): void;
    public function truncate(string $table): void;
    public function dropTable(string $table): void;

    // Транзакции
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
}
