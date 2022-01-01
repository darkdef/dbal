<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Type;

interface TypeInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function getPhpValue($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getDbValue($value);

    /**
     * @param mixed $value
     * @return string
     */
    public function getDbValueAsString($value): string;
}
