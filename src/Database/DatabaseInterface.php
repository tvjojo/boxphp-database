<?php
/**
 * DatabaseInterface 数据库接口
 */
namespace BoxPHP\Database\Database;

interface DatabaseInterface
{
    public function connect(string $dsn, string $username = '', string $password = '', array $options = []): bool;
    public function disconnect(): void;
    public function isConnected(): bool;

    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): int;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, string $where, array $whereParams = []): int;
    public function delete(string $table, string $where, array $whereParams = []): int;

    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function inTransaction(): bool;

    public function lastInsertId(): string;
    public function rowCount(): int;
    public function errorCode(): string;
    public function errorInfo(): array;
}
