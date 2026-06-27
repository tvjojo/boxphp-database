<?php
/**
 * Database 包测试 - 修正版 v3
 */
require_once __DIR__ . '/../vendor/autoload.php';

use BoxPHP\Database\Database\QueryBuilder;
use BoxPHP\Database\Database\PdoConnection;
use BoxPHP\Database\Database\DatabaseInterface;

echo "=== BoxPHP Database Package Tests ===\n\n";
$passed = 0;
$failed = 0;

// Mock DatabaseInterface for testing
$mockDb = new class implements DatabaseInterface {
    public function connect(string $dsn, string $username = '', string $password = '', array $options = []): bool { return true; }
    public function disconnect(): void {}
    public function isConnected(): bool { return true; }
    public function query(string $sql, array $params = []): array { return []; }
    public function execute(string $sql, array $params = []): int { return 0; }
    public function insert(string $table, array $data): int { return 1; }
    public function update(string $table, array $data, string $where, array $whereParams = []): int { return 1; }
    public function delete(string $table, string $where, array $whereParams = []): int { return 1; }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollback(): bool { return true; }
    public function inTransaction(): bool { return false; }
    public function lastInsertId(): string { return '1'; }
    public function rowCount(): int { return 1; }
    public function errorCode(): string { return ''; }
    public function errorInfo(): array { return []; }
};

// Test 1: DatabaseInterface
echo "1. DatabaseInterface\n";
try {
    assert($mockDb->connect('sqlite::memory:') === true);
    assert($mockDb->isConnected() === true);
    assert($mockDb->beginTransaction() === true);
    assert($mockDb->commit() === true);
    assert($mockDb->insert('users', ['name' => 'John']) === 1);
    assert($mockDb->errorCode() === '');
    
    echo "   ✓ DatabaseInterface tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: QueryBuilder SELECT
echo "2. QueryBuilder SELECT\n";
try {
    $mockPdo = new class extends PdoConnection {
        public function __construct() {}
        public function connect(string $dsn = '', string $username = '', string $password = '', array $options = []): bool { return true; }
    };
    
    $qb = QueryBuilder::table($mockPdo, 'users');
    
    // Simple select
    $sql = $qb->select('id', 'name', 'email')->toSql();
    assert(str_contains($sql, 'SELECT id, name, email'));
    assert(str_contains($sql, 'FROM `users`'));
    
    // With where
    $qb->reset();
    $sql = $qb->where('status', 'active')->toSql();
    assert(str_contains($sql, 'WHERE status = ?'));
    
    // With order by and limit
    $qb->reset();
    $sql = $qb->orderBy('created_at', 'DESC')->limit(10)->toSql();
    assert(str_contains($sql, 'ORDER BY created_at DESC'));
    assert(str_contains($sql, 'LIMIT 10'));
    
    // Multiple conditions
    $qb->reset();
    $sql = $qb->where('user_id', 1)
        ->where('status', 'pending')
        ->where('amount', '>', 100)
        ->toSql();
    assert(str_contains($sql, 'WHERE user_id = ?'));
    assert(str_contains($sql, 'AND status = ?'));
    assert(str_contains($sql, 'AND amount > ?'));
    
    // IN
    $qb->reset();
    $sql = $qb->whereIn('id', [1, 2, 3])->toSql();
    assert(str_contains($sql, 'WHERE id IN'));
    
    // JOIN
    $qb->reset();
    $sql = $qb->select('users.*', 'orders.total')
        ->join('orders', 'users.id', '=', 'orders.user_id')
        ->toSql();
    assert(str_contains($sql, 'JOIN orders ON users.id = orders.user_id'));
    
    // GROUP BY
    $qb->reset();
    $sql = $qb->select('user_id', 'COUNT(*) as count')
        ->groupBy('user_id')
        ->toSql();
    assert(str_contains($sql, 'GROUP BY user_id'));
    
    echo "   ✓ QueryBuilder SELECT tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
