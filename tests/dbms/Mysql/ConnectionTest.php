<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

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
}
