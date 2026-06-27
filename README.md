# boxphp/database

BoxPHP 数据库包 - PDO 连接、查询构建器及连接池

## 安装

```bash
composer require boxphp/database
```

## 使用

### PDO 连接

```php
use BoxPHP\Database\Database\PdoConnection;

$db = new PdoConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'myapp',
    'username' => 'root',
    'password' => '',
]);

$db->connect();
$users = $db->select('SELECT * FROM users WHERE id = ?', [1]);
```

### 查询构建器

```php
use BoxPHP\Database\Database\QueryBuilder;

// SELECT
$users = (new QueryBuilder('users'))
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// INSERT
$id = (new QueryBuilder('users'))
    ->insert(['name' => 'John', 'email' => 'john@example.com']);

// UPDATE
=count = (new QueryBuilder('users'))
    ->where('id', 1)
    ->update(['status' => 'inactive']);

// DELETE
=count = (new QueryBuilder('users'))
    ->where('id', 1)
    ->delete();

// 聚合
$count = (new QueryBuilder('users'))
    ->where('status', 'active')
    ->count();

$sum = (new QueryBuilder('orders'))
    ->where('user_id', 1)
    ->sum('amount');
```

### 连接池

```php
use BoxPHP\Database\Database\DatabasePool;

$pool = new DatabasePool([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'myapp',
    'username' => 'root',
    'password' => '',
    'pool_max_size' => 10,
]);

$db = $pool->get();
$users = $db->select('SELECT * FROM users');
$pool->put($db);
```

### 事务

```php
$db->beginTransaction();
try {
    $db->execute('UPDATE accounts SET balance = balance - ? WHERE id = ?', [100, 1]);
    $db->execute('UPDATE accounts SET balance = balance + ? WHERE id = ?', [100, 2]);
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

## 依赖

- PHP >= 8.1
- boxphp/core ^1.0
- PDO 扩展
