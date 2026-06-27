<?php
/**
 * BoxPHP Framework
 *
 * Copyright 2026 BoxPHP
 * By tvjojo, asterhuang, 黄波涛; 5viv.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
