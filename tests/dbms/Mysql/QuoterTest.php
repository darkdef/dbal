<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\DbalMysql\Schema\Quoter;

/**
 * @group quoter
 */
class QuoterTest extends TestCase
{
    public function testQuoteValue(): void
    {
        $quoter = new Quoter($this->mockConnection());

        $this->assertEquals(123, $quoter->quoteValue(123));
        $this->assertEquals("'string'", $quoter->quoteValue('string'));
        $this->assertEquals("'str-ing'", $quoter->quoteValue('str-ing'));
        $this->assertEquals("'str\\\\ing'", $quoter->quoteValue('str\ing'));
        $this->assertEquals("'It\'s interesting'", $quoter->quoteValue("It's interesting"));
        // @todo integration test for mysql values. Include sql-injection
    }

    public function testQuoteTableName(): void
    {
        $quoter = new Quoter($this->mockConnection());

        $this->assertEquals('`table`', $quoter->quoteTableName('table'));
        $this->assertEquals('`table`', $quoter->quoteTableName('`table`'));
        // @todo - need replace to DOT TableName
        // @todo - need replace to DOT TableName
//        $this->assertEquals('`schema`.`table`', $quoter->quoteTableName('schema.table'));
//        $this->assertEquals('`schema`.`table`', $quoter->quoteTableName('schema.`table`'));
//        $this->assertEquals('`schema`.`table`', $quoter->quoteTableName('`schema`.`table`'));
        $this->assertEquals('{{table}}', $quoter->quoteTableName('{{table}}'));
        $this->assertEquals('{{%table}}', $quoter->quoteTableName('{{%table}}'));
        $this->assertEquals('`table(0)`', $quoter->quoteTableName('table(0)'));
    }

    public function testQuoteColumnName(): void
    {
        $quoter = new Quoter($this->mockConnection());

        $this->assertEquals('`column`', $quoter->quoteColumnName('column'));
        $this->assertEquals('`column`', $quoter->quoteColumnName('`column`'));
        $this->assertEquals('[[column]]', $quoter->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $quoter->quoteColumnName('{{column}}'));
//        $this->assertEquals('(column)', $quoter->quoteColumnName('(column)'));

        $this->assertEquals('`column`', $quoter->quoteSql('[[column]]'));
        $this->assertEquals('`column`', $quoter->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $quoter = new Quoter($this->mockConnection());

        $this->assertEquals('`table`.`column`', $quoter->quoteColumnName('table.column'));
        $this->assertEquals('`table`.`column`', $quoter->quoteColumnName('table.`column`'));
        $this->assertEquals('`table`.`column`', $quoter->quoteColumnName('`table`.column'));
        $this->assertEquals('`table`.`column`', $quoter->quoteColumnName('`table`.`column`'));

        $this->assertEquals('[[table.column]]', $quoter->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}.`column`', $quoter->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}.`column`', $quoter->quoteColumnName('{{table}}.`column`'));
        $this->assertEquals('{{table}}.[[column]]', $quoter->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}.`column`', $quoter->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}.`column`', $quoter->quoteColumnName('{{%table}}.`column`'));

        $this->assertEquals('`table`.`column`', $quoter->quoteSql('[[table.column]]'));
        $this->assertEquals('`table`.`column`', $quoter->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $quoter->quoteSql('{{table}}.`column`'));
        $this->assertEquals('`table`.`column`', $quoter->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $quoter->quoteSql('{{%table}}.`column`'));
    }

    private function mockConnection(): ConnectionInterface
    {
        $mock = $this->createMock(ConnectionInterface::class);
        $mock->method('getTablePrefix')->willReturn('');

        return $mock;
    }
}
