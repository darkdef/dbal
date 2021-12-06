<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Command;

use Yiisoft\Dbal\Type\TypeInterface;

interface ValueInterface
{
    public function __construct($value, TypeInterface $type);

    public function getValue();

    public function getType(): TypeInterface;

    public function asString(): string;
}
