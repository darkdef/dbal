<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Exception\Exception;
use Yiisoft\Dbal\Transaction\TransactionInterface;

class ConnectionTest extends TestCase
{
    public function testConnection(): void
    {
        $this->assertIsObject($this->connection);
    }

    public function testOpenCloseConnection(): void
    {
        $this->connection->open();
        $this->assertTrue($this->connection->isActive());

        $this->connection->close();
        $this->assertFalse($this->connection->isActive());
    }

    public function testServiceVersion(): void
    {
        $this->connection->open();
        $this->assertIsString($this->connection->getServerVersion());
    }

    public function testGetDriverName(): void
    {
        $this->assertIsString($this->connection->getDriverName());
    }

    public function testTransaction(): void
    {
        $db = $this->getConnection(true);

        $this->assertNull($db->getTransaction());

        $transaction = $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertTrue($transaction->isActive());

        $db->createCommand("insert into profile([[description]]) values(:description)", ['description' =>'test transaction'])->execute();

        $transaction->rollBack();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(0, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
        )->queryScalar());

        $transaction = $db->beginTransaction();

        $db->createCommand("insert into profile([[description]]) values(:description)", ['description' =>'test transaction'])->execute();

        $transaction->commit();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
        )->queryScalar());
    }

    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection(true);

        $transaction = $db->beginTransaction(TransactionInterface::READ_UNCOMMITTED);

        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::READ_COMMITTED);

        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::REPEATABLE_READ);

        $transaction->commit();

        $transaction = $db->beginTransaction(TransactionInterface::SERIALIZABLE);

        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);
    }

    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(Exception::class);

        $db->transaction(function () use ($db) {
            $db->createCommand("insert into profile([[description]]) values(:description)", ['description' =>'test transaction shortcut'])->execute();
            throw new Exception('Exception in transaction shortcut');
        });

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();
        $this->assertEquals(0, $profilesCount, 'profile should not be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCorrect(): void
    {
        $db = $this->getConnection(true);

        $result = $db->transaction(static function () use ($db) {
            $db->createCommand("insert into profile([[description]]) values(:description)", ['description' =>'test transaction shortcut'])->execute();
            return true;
        });

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnection(true);

        $result = $db->transaction(static function (ConnectionInterface $db) {
            $db->createCommand("insert into profile([[description]]) values(:description)", ['description' =>'test transaction shortcut'])->execute();
            return true;
        }, TransactionInterface::READ_UNCOMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    /**
     * Tests nested transactions with partial rollback.
     *
     * {@see https://github.com/yiisoft/yii2/issues/9851}
     */
    public function testNestedTransaction(): void
    {
        $db = $this->getConnection();

        $db->transaction(function (ConnectionInterface $db) {
            $this->assertNotNull($db->getTransaction());

            $db->transaction(function (ConnectionInterface $db) {
                $this->assertNotNull($db->getTransaction());
                $db->getTransaction()->rollBack();
            });

            $this->assertNotNull($db->getTransaction());
        });
    }
}
