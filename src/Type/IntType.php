<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Type;

final class IntType implements TypeInterface
{
    private $phpValue = null;

    private $dbValue = null;

    public function getPhpValue($value)
    {
        if ($this->phpValue === $value) {
            return $this->dbValue;
        }
        $this->dbValue = $value;

        // @todo Need implement of convertation
        $this->phpValue = $this->dbValue;

        return $this->phpValue;
    }

    public function getDbValue($value)
    {
        if ($this->dbValue === $value) {
            return $this->phpValue;
        }
        $this->phpValue = $value;

        // @todo Need implement of convertation
        $this->dbValue = $this->phpValue;

        return $this->dbValue;
    }

    public function getDbValueAsString($value): string
    {
        $value = $this->getDbValue($value);

        return sprintf('%d', $value);
    }
}
