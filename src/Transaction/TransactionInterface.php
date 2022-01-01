<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Transaction;

use Throwable;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Exception\Exception;
use Yiisoft\Dbal\Exception\InvalidConfigException;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling {@see Connection::beginTransaction()}.
 *
 * The following code is a typical example of using transactions (note that some DBMS may not support transactions):
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch (\Throwable $e) {
 *     $transaction->rollBack();
 *     throw $e;
 * }
 * ```
 *
 * @property bool $isActive Whether this transaction is active. Only an active transaction can {@see commit()} or
 * {@see rollBack()}. This property is read-only.
 * @property string $isolationLevel The transaction isolation level to use for this transaction. This can be one of
 * {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE} but also a string
 * containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is write-only.
 * @property int $level The current nesting level of the transaction. This property is read-only.
 */
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

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void;

    /**
     * Releases an existing savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function releaseSavepoint(string $name): void;

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBackSavepoint(string $name): void;

    /**
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool;
}
