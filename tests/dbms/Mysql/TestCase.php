<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Dbal\Cache\SchemaCache;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Connection\ConnectionPdoInterface;
use Yiisoft\Dbal\Exception\Exception;
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

    protected ?CacheInterface $cache = null;
    protected ConnectionInterface $connection;
    protected ?SchemaCache $schemaCache = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchemaCache();

        $this->connection = $this->createConnection();
        $this->connection->setSchemaCache($this->schemaCache);
//        $this->connection->setCharset(self::DB_CHARSET);
    }

    protected function createConnection(): ?ConnectionPdoInterface
    {
        return new Connection(self::DB_DSN, self::DB_USERNAME, self::DB_PASSWORD);
    }

    protected function createSchemaCache(): SchemaCache
    {
        if ($this->schemaCache === null) {
            $this->schemaCache = new SchemaCache($this->createCache());
        }
        return $this->schemaCache;
    }

    protected function createCache(): Cache
    {
        if ($this->cache === null) {
            $this->cache = new Cache(new ArrayCache());
        }
        return $this->cache;
    }

    public function getConnection($reset = false): ConnectionPdoInterface
    {
        if ($reset !== false) {
            if ($this->connection) {
                $this->connection->close();
            }

            $this->connection = $this->createConnection();

            try {
                $this->prepareDatabase();
            } catch (Exception $e) {
                $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
            }
        }

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
