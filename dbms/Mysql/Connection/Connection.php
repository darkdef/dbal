<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Connection;

use Yiisoft\Dbal\Command\CommandInterface;
use Yiisoft\Dbal\Connection\ConnectionPdoInterface;

use Yiisoft\Dbal\Transaction\TransactionInterface;
use Yiisoft\DbalMysql\Command\Command;

use \PDO;

final class Connection implements ConnectionPdoInterface
{
    private ?PDO $pdo = null;

    private string $dsn;

    private ?string $username;

    private ?string $password;

    private ?array $options;

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    public function createCommand(?string $sql = null, array $params = []): CommandInterface
    {
        return new Command($this, $sql, $params);
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function getServerVersion(): string
    {
        return (string)$this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function isActive(): bool
    {
        return $this->pdo !== null;
    }

    public function open(): void
    {
        if (!$this->pdo) {
            $this->pdo = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        }
    }

    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->pdo = null;
//            $this->transaction = null;
        }
    }

//    public function beginTransaction(string $isolationLevel = null): TransactionInterface
//    {
//        // TODO: Implement beginTransaction() method.
//    }
    public function getPdo(): \PDO
    {
        if ($this->pdo === null) {
            throw new \Exception('PDO not initialized');
        }
        return $this->pdo;
    }
}
