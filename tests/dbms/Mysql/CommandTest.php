<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use Yiisoft\DbalMysql\Command\Command;

class CommandTest extends TestCase
{
    public function testCreate(): void
    {
        $command = $this->connection->createCommand();
        $this->assertInstanceOf(Command::class, $command);
    }

    public function testSimpleCommand(): void
    {
        $this->prepareDatabase();

        $command = new Command($this->connection, "show tables");
        $this->assertGreaterThanOrEqual(1, $command->execute());

        $command = new Command($this->connection, "select * from animal");
        $this->assertGreaterThanOrEqual(1, $command->execute());

//        $command = new Command($this->connection, "select count(*) as cnt from animal where id > :id", [':id' => 0]);
//        $command->execute();
//        $results = $command->getResults();
//        $this->assertCount(1, $results);
//        $this->assertArrayHasKey('cnt', current($results));
//        $this->assertGreaterThanOrEqual(1, current($results)['cnt']);
    }

    public function testBindQuery(): void
    {
        $this->prepareDatabase();

        $command = $this->connection->createCommand('select * from category where id=:id')->bindValue(':id', 1);
        $this->assertEquals(1, $command->execute());

        $command = $this->connection->createCommand('select * from category where id=:id')->bindValues([':id' => 1]);
        $this->assertEquals(1, $command->execute());

        $command = $this->connection->createCommand('select * from category where name=:name')->bindValues([':name' => ['Books']]);
        $this->assertEquals(1, $command->execute());
    }

    public function testBindSql(): void
    {
        $sql = 'select * from `category`';
        $command = $this->connection->createCommand()->setRawSql('select * from `category`');
        $this->assertEquals($sql, $command->getSql());

        $command = $this->connection->createCommand()->setSql('select * from `category`');
        $this->assertEquals($sql, $command->getSql());

        $command = $this->connection->createCommand('select * from {{category}}');
        $this->assertEquals($sql, $command->getSql());
    }

    public function testRawSql(): void
    {
        $sql = 'select * from `category`';
        $command = $this->connection->createCommand('select * from {{category}}');
        $this->assertEquals($sql, $command->getRawSql());

        $sql = 'select * from `category` where id=1';
        $command = $this->connection->createCommand('select * from {{category}} where id=:id', [':id' => 1]);
        $this->assertEquals($sql, $command->getRawSql());

        $sql = 'select * from `category` where id=NULL';
        $command = $this->connection->createCommand('select * from {{category}} where id=:id', [':id' => null]);
        $this->assertEquals($sql, $command->getRawSql());

        $sql = 'select * from `category` where name=\'test\'';
        $command = $this->connection->createCommand('select * from {{category}} where name=:name', [':name' => ['test', 'string']]);
        $this->assertEquals($sql, $command->getRawSql());
    }

    public function testFetchMode(): void
    {
        $db = $this->getConnection();
        $this->prepareDatabase();

        $sql = 'SELECT * FROM {{customer}}';
        $command = $db->createCommand($sql);
        $result = $command->queryOne();
        $this->assertTrue(is_array($result) && isset($result['id']));

    }
}
