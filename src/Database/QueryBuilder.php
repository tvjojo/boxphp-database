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
 * QueryBuilder 查询构建器
 */
namespace BoxPHP\Database\Database;

class QueryBuilder
{
    protected PdoConnection $db;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $whereParams = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $groups = [];
    protected array $having = [];
    protected array $joins = [];

    public function __construct(PdoConnection $db)
    {
        $this->db = $db;
    }

    public static function table(PdoConnection $db, string $table): static
    {
        $builder = new static($db);
        $builder->table = $table;
        return $builder;
    }

    // ===== SELECT =====

    public function select(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = "{$column} {$operator} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function whereRaw(string $sql, array $params = []): static
    {
        $this->wheres[] = $sql;
        array_push($this->whereParams, ...$params);
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        array_push($this->whereParams, ...$values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy[] = "{$column} " . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function groupBy(string ...$columns): static
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): static
    {
        $this->having[] = "{$column} {$operator} ?";
        $this->whereParams[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = "JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    // ===== 构建 SQL =====

    protected function buildSelect(): string
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM `{$this->table}`";

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(', ', $this->groups);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    // ===== 执行 =====

    public function get(): array
    {
        $sql = $this->buildSelect();
        return $this->db->query($sql, $this->whereParams);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $row = $this->select($column)->first();
        return $row[$column] ?? null;
    }

    public function count(): int
    {
        $row = $this->select('COUNT(*) as count')->first();
        return (int)($row['count'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function sum(string $column): float
    {
        $row = $this->select("SUM({$column}) as sum")->first();
        return (float)($row['sum'] ?? 0);
    }

    public function avg(string $column): float
    {
        $row = $this->select("AVG({$column}) as avg")->first();
        return (float)($row['avg'] ?? 0);
    }

    public function max(string $column): mixed
    {
        $row = $this->select("MAX({$column}) as max")->first();
        return $row['max'] ?? null;
    }

    public function min(string $column): mixed
    {
        $row = $this->select("MIN({$column}) as min")->first();
        return $row['min'] ?? null;
    }

    // ===== INSERT/UPDATE/DELETE =====

    public function insert(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    public function insertGetId(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    public function update(array $data): int
    {
        $where = !empty($this->wheres) ? implode(' AND ', $this->wheres) : '1=1';
        return $this->db->update($this->table, $data, $where, $this->whereParams);
    }

    public function delete(): int
    {
        $where = !empty($this->wheres) ? implode(' AND ', $this->wheres) : '1=1';
        return $this->db->delete($this->table, $where, $this->whereParams);
    }

    // ===== 重置 =====

    public function reset(): void
    {
        $this->columns = ['*'];
        $this->wheres = [];
        $this->whereParams = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->groups = [];
        $this->having = [];
        $this->joins = [];
    }

    public function toSql(): string
    {
        return $this->buildSelect();
    }
}
