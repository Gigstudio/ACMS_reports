<?php
namespace GigReportServer\System\Engine;

interface DatabaseInterface
{
    public function exec(string $sql, array $params = [], string $message = ''): \PDOStatement;
    public function value(string $sql, array $params = [], string $message = ''): mixed;

    public function get(string $table, array $where = [], array $fields = ['*']): array;
    public function first(string $table, array $where = [], array $fields = ['*']): ?array;

    public function insert(string $table, array $data): bool;
    public function update(string $table, array $data, array $where): bool;
    public function delete(string $table, array $where): bool;

    public function tableExists(string $table): bool;
    public function createTable(string $table, array $schema): bool;
    public function describeTable(string $table): array;

    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;

    public function truncate(string $table): void;
    public function dropTable(string $table): void;
}
