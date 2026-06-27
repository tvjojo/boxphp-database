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
 * PdoConnection PDO 数据库连接
 */
namespace BoxPHP\Database\Database;

class PdoConnection implements DatabaseInterface
{
    protected ?\PDO $pdo = null;
    protected string $dsn;
    protected string $username;
    protected string $password;
    protected array $options;
    protected bool $connected = false;

    public function __construct(array $config = [])
    {
        $this->dsn = $config['dsn'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->options = $config['options'] ?? [];

        if ($this->dsn === '' && isset($config['driver'])) {
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';

            $this->dsn = "{$config['driver']}:host={$host};port={$port};dbname={$database};charset={$charset}";
        }
    }

    public function connect(string $dsn, string $username = '', string $password = '', array $options = []): bool
    {
        $this->dsn = $dsn ?: $this->dsn;
        $this->username = $username ?: $this->username;
        $this->password = $password ?: $this->password;
        $this->options = array_merge([
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_BOTH,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ], $this->options, $options);

        try {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->connected = true;
            return true;
        } catch (\PDOException $e) {
            $this->connected = false;
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->pdo !== null;
    }

    public function getPdo(): ?\PDO
    {
        return $this->pdo;
    }

    public function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            $this->connect($this->dsn, $this->username, $this->password);
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insert(string $table, array $data): int
    {
        $this->ensureConnected();
        $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $this->ensureConnected();
        $setClauses = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$setClauses} WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $this->ensureConnected();
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereParams);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        $this->ensureConnected();
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function rowCount(): int
    {
        return 0; // PDO rowCount() 对 SELECT 不可靠
    }

    public function errorCode(): string
    {
        return $this->pdo->errorCode();
    }

    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }
}
