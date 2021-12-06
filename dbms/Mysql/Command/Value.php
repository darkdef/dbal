<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Command;

use Yiisoft\Dbal\Command\ValueInterface;
use Yiisoft\Dbal\Type\TypeInterface;

final class Value implements ValueInterface
{
    /**
     * @var mixed
     */
    private $value;

    private TypeInterface $type;

    public function __construct($value, TypeInterface $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getType(): TypeInterface
    {
        return $this->type;
    }

    public function asString(): string
    {
        if ($this->value === null) {
            return 'NULL';
        }
        // @TODO if ExpressionInterface

        return $this->type->getDbValueAsString($this->value);
    }
}
