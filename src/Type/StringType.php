<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Type;

final class StringType implements TypeInterface
{
    private $phpValue = null;

    private $dbValue = null;

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getDbValueAsString($value): string
    {
        /** @psalm-var string */
        $value = $this->getDbValue($value);

        return sprintf('\'%s\'', $value);
    }
}
