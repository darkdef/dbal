<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Connection;

use PDO;

interface ConnectionPdoInterface extends ConnectionInterface
{
    public function getPdo(): PDO;
}
