<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Type;

interface TypeInterface
{
    public function getPhpValue($value);

    public function getDbValue($value);

    public function getDbValueAsString($value): string;
}
