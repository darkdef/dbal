<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Connection;

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
     * Returns the schema information for the database opened by this connection.
     *
     * @return SchemaInterface the schema information for the database opened by this connection.
     */
//    public function getSchema(): SchemaInterface;

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
//    public function beginTransaction(string $isolationLevel = null): TransactionInterface;
}
