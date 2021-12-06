<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Command;

use Yiisoft\Dbal\Connection\ConnectionInterface;

use Exception;
use Throwable;

interface CommandInterface
{
    public function __construct(ConnectionInterface $db, ?string $sql = null, array $params = []);

    public function setSql(string $sql): self;

    /**
     * Returns the SQL statement for this command.
     *
     * @return string|null the SQL statement to be executed.
     */
    public function getSql(): ?string;

    public function setRawSql(string $sql): self;

    public function getRawSql(): string;

    /**
     * Executes the SQL statement.
     *
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     *
     * @throws Throwable
     * @throws Exception execution failed.
     *
     * @return int number of rows affected by the execution.
     */
    public function execute(): int;

    public function bindValue($name, $value, string $dataType = ''): self;

    public function bindValues(array $values): self;

    /**
     * @return array|false
     */
    public function queryOne();

    /**
     * @return array
     */
    public function queryAll(): array;

    /**
     * @return mixed
     */
    public function queryScalar();

    /**
     * @return array
     */
    public function queryColumn(): array;
}
