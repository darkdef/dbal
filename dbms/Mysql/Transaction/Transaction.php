<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Transaction;

use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Dbal\AwareTrait\LoggerAwareTrait;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Transaction\TransactionInterface;
use Yiisoft\Dbal\Exception\Exception;
use Yiisoft\Dbal\Exception\InvalidConfigException;
use Yiisoft\Dbal\Exception\NotSupportedException;

class Transaction implements TransactionInterface
{
    use LoggerAwareTrait;

    private int $level = 0;
    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->level > 0 && $this->db && $this->db->isActive();
    }

    /**
     * @inheritDoc
     */
    public function begin(?string $isolationLevel = null): void
    {
        if ($this->db === null) {
            throw new InvalidConfigException('Transaction::db must be set.');
        }

        $this->db->open();

        if ($this->level === 0) {
            if ($isolationLevel !== null) {
                $this->setTransactionIsolationLevel($isolationLevel);
            }

            if ($this->logger !== null) {
                $this->logger->log(
                    LogLevel::DEBUG,
                    'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : '')
                    . ' ' . __METHOD__
                );
            }

            $this->db->getPDO()->beginTransaction();
            $this->level = 1;

            return;
        }

        if ($this->supportsSavepoint()) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::DEBUG, 'Set savepoint ' . $this->level . ' ' . __METHOD__);
            }

            $this->createSavepoint('LEVEL' . $this->level);
        } else {
            if ($this->logger !== null) {
                $this->logger->log(
                    LogLevel::DEBUG,
                    'Transaction not started: nested transaction not supported ' . __METHOD__
                );
            }

            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->level++;
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->level--;
        if ($this->level === 0) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::DEBUG, 'Commit transaction ' . __METHOD__);
            }

            $this->db->getPDO()->commit();

            return;
        }

        if ($this->supportsSavepoint()) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::DEBUG, 'Release savepoint ' . $this->level . ' ' . __METHOD__);
            }

            $this->releaseSavepoint('LEVEL' . $this->level);
        } else {
            if ($this->logger !== null) {
                $this->logger->log(
                    LogLevel::INFO,
                    'Transaction not committed: nested transaction not supported ' . __METHOD__
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function rollBack(): void
    {
        if (!$this->isActive()) {
            /**
             * do nothing if transaction is not active: this could be the transaction is committed but the event handler
             * to "commitTransaction" throw an exception
             */
            return;
        }

        $this->level--;
        if ($this->level === 0) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::INFO, 'Roll back transaction ' . __METHOD__);
            }

            $this->db->getPDO()->rollBack();

            return;
        }

        if ($this->supportsSavepoint()) {
            if ($this->logger !== null) {
                $this->logger->log(LogLevel::DEBUG, 'Roll back to savepoint ' . $this->level . ' ' . __METHOD__);
            }

            $this->rollBackSavepoint('LEVEL' . $this->level);
        } else {
            if ($this->logger !== null) {
                $this->logger->log(
                    LogLevel::INFO,
                    'Transaction not rolled back: nested transaction not supported ' . __METHOD__
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function setIsolationLevel(string $level): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }

        if ($this->logger !== null) {
            $this->logger->log(
                LogLevel::DEBUG,
                'Setting transaction isolation level to ' . $this->level . ' ' . __METHOD__
            );
        }

        $this->setTransactionIsolationLevel($level);
    }

    /**
     * @inheritDoc
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @inheritDoc
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function releaseSavepoint(string $name): void
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

     /**
     * Sets the isolation level of the current transaction.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     *
     * This can be one of {@see Transaction::READ_UNCOMMITTED}, {@see Transaction::READ_COMMITTED},
     * {@see Transaction::REPEATABLE_READ} and {@see Transaction::SERIALIZABLE} but also a string containing DBMS
     * specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    private function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    /**
     * @inheritDoc
     */
    public function supportsSavepoint(): bool
    {
        return true;
    }
}
