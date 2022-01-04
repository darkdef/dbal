<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Connection;

use Exception;
use PDOException;
use Psr\Log\LogLevel;
use Throwable;
use \PDO;

use Yiisoft\Dbal\Cache\SchemaCache;
use Yiisoft\Dbal\Exception\InvalidConfigException;
use Yiisoft\Dbal\AwareTrait\LoggerAwareTrait;
use Yiisoft\Dbal\AwareTrait\ProfilerAwareTrait;
use Yiisoft\Dbal\Command\CommandInterface;
use Yiisoft\Dbal\Connection\ConnectionPdoInterface;
use Yiisoft\Dbal\Schema\QuoterInterface;
use Yiisoft\Dbal\Schema\SchemaInterface;
use Yiisoft\Dbal\Transaction\TransactionInterface;

use Yiisoft\DbalMysql\Command\Command;
use Yiisoft\DbalMysql\Schema\Quoter;
use Yiisoft\DbalMysql\Schema\AbstractSchema;
use Yiisoft\DbalMysql\Transaction\Transaction;

final class Connection implements ConnectionPdoInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    private ?PDO $pdo = null;
    private string $dsn;
    private ?string $username;
    private ?string $password;
    private ?array $options;
    private string $tablePrefix = '';

    private QuoterInterface $quoter;
    private ?TransactionInterface $transaction = null;
    private ?SchemaInterface $schema = null;

    private ?SchemaCache $schemaCache = null;

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

    public function getQuoter(): QuoterInterface
    {
        if (empty($this->quoter)) {
            $this->quoter = new Quoter($this);
        }

        return $this->quoter;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getSchema(): SchemaInterface
    {
        if (empty($this->schema)) {
            $this->schema = new AbstractSchema($this, $this->schemaCache);
        }

        return $this->schema;
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
        if (!empty($this->pdo)) {
            return;
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        $token = 'Opening DB connection: ' . $this->dsn;

        try {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::INFO, $token);
            }

            if ($this->profiler !== null) {
                $this->profiler->begin($token, [__METHOD__]);
            }

            $this->pdo = $this->createPdoInstance();

            $this->initConnection();

            if ($this->profiler !== null) {
                $this->profiler->end($token, [__METHOD__]);
            }
        } catch (PDOException $e) {
            if ($this->profiler !== null) {
                $this->profiler->end($token, [__METHOD__]);
            }

            if ($this->logger !== null) {
                $this->logger->log(LogLevel::ERROR, $token);
            }

            throw new \Yiisoft\Dbal\Exception\Exception($e->getMessage(), $e->errorInfo, $e);
        }

    }

    public function close(): void
    {
        if ($this->pdo !== null) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::DEBUG, 'Closing DB connection: ' . $this->dsn . ' ' . __METHOD__);
            }

            $this->pdo = null;
            $this->transaction = null;
        }
    }

    public function getPdo(): \PDO
    {
        if ($this->pdo === null) {
            throw new \Exception('PDO not initialized');
        }
        return $this->pdo;
    }

    public function beginTransaction(string $isolationLevel = null): TransactionInterface
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->transaction = new Transaction($this);
            if ($this->logger !== null) {
                $transaction->setLogger($this->logger);
            }
        }

        $transaction->begin($isolationLevel);

        return $transaction;

    }

    public function transaction(callable $callback, string $isolationLevel = null)
    {
        $transaction = $this->beginTransaction($isolationLevel);

        $level = $transaction->getLevel();

        try {
            $result = $callback($this);

            if ($transaction->isActive() && $transaction->getLevel() === $level) {
                $transaction->commit();
            }
        } catch (Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);

            throw $e;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setSchemaCache(?SchemaCache $schemaCache): void
    {
        $this->schemaCache = $schemaCache;
    }

    /**
     * Returns the currently active transaction.
     *
     * @return TransactionInterface|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?TransactionInterface
    {
        return $this->transaction && $this->transaction->isActive() ? $this->transaction : null;
    }

    protected function createPdoInstance(): PDO
    {
        return $this->pdo = new PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        $pdo = $this->getPDO();

        if ($pdo !== null) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
            }

            $charset = $this->getCharset();

            if ($charset !== null) {
                $pdo->exec('SET NAMES ' . $pdo->quote($charset));
            }
        }
    }

    /**
     * Rolls back given {@see TransactionInterface} object if it's still active and level match. In some cases rollback can fail,
     * so this method is fail safe. Exceptions thrown from rollback will be caught and just logged with
     * {@see logger->log()}.
     *
     * @param TransactionInterface $transaction TransactionInterface object given from {@see beginTransaction()}.
     * @param int $level TransactionInterface level just after {@see beginTransaction()} call.
     */
    private function rollbackTransactionOnLevel(TransactionInterface $transaction, int $level): void
    {
        if ($transaction->isActive() && $transaction->getLevel() === $level) {
            /**
             * {@see https://github.com/yiisoft/yii2/pull/13347}
             */
            try {
                $transaction->rollBack();
            } catch (Exception $e) {
                if ($this->logger !== null) {
                    $this->logger->log(LogLevel::ERROR, $e, [__METHOD__]);
                    /** hide this exception to be able to continue throwing original exception outside */
                }
            }
        }
    }

    private function getEmulatePrepare(): ?bool
    {
        return $this->options['emulatePrepare'] ?? null;
    }

    private function getCharset(): ?string
    {
        return $this->options['charset'] ?? null;
    }
}
