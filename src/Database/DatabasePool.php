<?php
/**
 * DatabasePool 数据库连接池
 */
namespace BoxPHP\Database\Database;

class DatabasePool
{
    /** @var PdoConnection[] */
    protected array $pool = [];

    protected array $available = [];
    protected int $maxSize;
    protected int $currentSize = 0;
    protected array $config;
    protected float $waitTimeout;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->maxSize = $config['pool_max_size'] ?? 5;
        $this->waitTimeout = $config['pool_wait_timeout'] ?? 3.0;
    }

    public function get(): PdoConnection
    {
        while (!empty($this->available)) {
            $conn = array_pop($this->available);
            if ($conn->isConnected()) {
                return $conn;
            }
            $this->currentSize--;
        }

        if ($this->currentSize < $this->maxSize) {
            $conn = $this->createConnection();
            $this->currentSize++;
            return $conn;
        }

        $start = microtime(true);
        while (microtime(true) - $start < $this->waitTimeout) {
            if (!empty($this->available)) {
                $conn = array_pop($this->available);
                if ($conn->isConnected()) {
                    return $conn;
                }
                $this->currentSize--;
            }
            usleep(10000);
        }

        throw new \RuntimeException('Database pool exhausted');
    }

    public function put(PdoConnection $conn): void
    {
        if ($conn->isConnected()) {
            $this->available[] = $conn;
        } else {
            $this->currentSize--;
        }
    }

    protected function createConnection(): PdoConnection
    {
        $conn = new PdoConnection($this->config);
        $conn->connect(
            $this->config['dsn'] ?? '',
            $this->config['username'] ?? '',
            $this->config['password'] ?? ''
        );
        return $conn;
    }

    public function getStatus(): array
    {
        return [
            'max_size' => $this->maxSize,
            'current_size' => $this->currentSize,
            'available' => count($this->available),
            'in_use' => $this->currentSize - count($this->available),
        ];
    }

    public function destroy(): void
    {
        foreach ($this->pool as $conn) {
            $conn->disconnect();
        }
        foreach ($this->available as $conn) {
            $conn->disconnect();
        }
        $this->pool = [];
        $this->available = [];
        $this->currentSize = 0;
    }
}
