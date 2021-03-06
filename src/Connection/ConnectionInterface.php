<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Connection;

use Throwable;
use Yiisoft\Dbal\Cache\SchemaCache;
use Yiisoft\Dbal\Command\CommandInterface;
use Yiisoft\Dbal\Schema\QuoterInterface;
use Yiisoft\Dbal\Schema\SchemaInterface;
use Yiisoft\Dbal\Transaction\TransactionInterface;

interface ConnectionInterface
{
    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     *
     * @return CommandInterface the DB command
     */
    public function createCommand(?string $sql = null, array $params = []): CommandInterface;

    public function getQuoter(): QuoterInterface;

    public function getTablePrefix(): string;

    public function setTablePrefix(string $value);

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string;

    /**
     * @return string the Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://secure.php.net/manual/en/pdo.construct.php) on the format of the DSN
     * string.
     *
     * For [SQLite](https://secure.php.net/manual/en/ref.pdo-sqlite.connection.php) you may use a
     * [path alias](guide:concept-aliases) for specifying the database path, e.g. `sqlite:@app/data/db.sql`.
     *
     * {@see charset}
     */
    public function getDsn(): string;

    public function getUsername(): ?string;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return SchemaInterface the schema information for the database opened by this connection.
     */
    public function getSchema(): SchemaInterface;

    /**
     * Returns a server version as a string comparable by {@see \version_compare()}.
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string;

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established
     */
    public function isActive(): bool;

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     */
    public function open(): void;

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void;

    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see TransactionInterface::begin()} for details.
     *
     * @return TransactionInterface the transaction initiated
     */
    public function beginTransaction(string $isolationLevel = null): TransactionInterface;

    /**
     * Executes callback provided in a transaction.
     *
     * @param callable $callback a valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction. {@see Transaction::begin()}
     * for details.
     *
     *@throws Throwable if there is any exception during query. In this case the transaction will be rolled back.
     *
     * @return mixed result of callback function
     */
    public function transaction(callable $callback, string $isolationLevel = null);

    /**
     * Returns the currently active transaction.
     *
     * @return TransactionInterface|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?TransactionInterface;

    /**
     * @param SchemaCache|null $schemaCache
     */
    public function setSchemaCache(?SchemaCache $schemaCache): void;
}
