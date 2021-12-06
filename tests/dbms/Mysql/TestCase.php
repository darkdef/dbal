<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\DbalMysql\Connection\Connection;

class TestCase extends AbstractTestCase
{
    protected const DB_CONNECTION_CLASS = Connection::class;
    protected const DB_DRIVERNAME = 'mysql';
    protected const DB_DSN = 'mysql:host=127.0.0.1;dbname=yiitest;port=3306';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/mysql.sql';
    protected const DB_USERNAME = 'root';
    protected const DB_PASSWORD = '';
    protected const DB_CHARSET = 'UTF8MB4';

    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(self::DB_DSN, self::DB_USERNAME, self::DB_PASSWORD);
//        $this->connection->setCharset(self::DB_CHARSET);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    protected function prepareDatabase(string $dsn = null, $fixture = null): void
    {
        $fixture = $fixture ?? self::DB_FIXTURES_PATH;

        $this->connection->open();

        $lines = explode(';', file_get_contents($fixture));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $this->connection->createCommand($line)->execute();
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection->close();
        unset(
            $this->cache,
            $this->connection,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
