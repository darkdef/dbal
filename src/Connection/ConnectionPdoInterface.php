<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Connection;

interface ConnectionPdoInterface extends ConnectionInterface
{
    public function getPdo(): \PDO;
}
