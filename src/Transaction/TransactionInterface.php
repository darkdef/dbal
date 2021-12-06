<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Transaction;

use Yiisoft\Dbal\Connection\ConnectionInterface;

interface TransactionInterface
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_COMMITTED = 'READ COMMITTED';

    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const SERIALIZABLE = 'SERIALIZABLE';

    public function __construct(ConnectionInterface $db);

    public function isActive(): bool;

    public function transaction(callable $callback, string $isolationLevel = null);

    /**
     * Begins a transaction.
     *
     * @param string|null $isolationLevel The {@see isolation level}[] to use for this transaction.
     */
    public function begin(?string $isolationLevel = null): void;

    /**
     * Commits a transaction.
     */
    public function commit(): void;

    /**
     * Rolls back a transaction.
     */
    public function rollBack(): void;

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However, this is not supported by all DBMS so you might rather specify the isolation level directly when calling
     * {@see begin()}.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE}
     * but also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public function setIsolationLevel(string $level): void;

    /**
     * @return int the nesting level of the transaction. 0 means the outermost level.
     */
    public function getLevel(): int;
}
