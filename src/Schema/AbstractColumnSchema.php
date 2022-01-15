<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Yiisoft\Dbal\Type\TypeInterface;

/**
 * ColumnSchema class describes the metadata of a column in a database table.
 */
class AbstractColumnSchema implements ColumnSchemaInterface
{
    // @todo temporarily is null
    private ?TypeInterface $type = null;

    private string $name;
    private bool $allowNull;
    private string $dbType;
    private $defaultValue;
    private ?array $enumValues = null;
    private ?int $size = null;
    private ?int $precision = null;
    private ?int $scale = null;
    private bool $isPrimaryKey = false;
    private bool $autoIncrement = false;
    private bool $unsigned = false;
    private ?string $comment = null;

    public function getType(): ?TypeInterface
    {
        return $this->type;
    }

    public function type(TypeInterface $value): void
    {
        $this->type = $value;
    }

    public function dbType(string $value): void
    {
        $this->dbType = $value;
    }

    public function getDbType(): string
    {
        return $this->dbType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAllowNull(): bool
    {
        return $this->allowNull;
    }

    /**
     * @return mixed default value of this column
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }

    /**
     * @return int display size of the column.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return int precision of the column data, if it is numeric.
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * @return int scale of the column data, if it is numeric.
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * @return bool whether this column is a primary key
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * @return bool whether this column is auto-incremental
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * @return bool whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @return string|null comment of this column. Not all DBMS support this.
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function name(string $value): void
    {
        $this->name = $value;
    }

    public function allowNull(bool $value): void
    {
        $this->allowNull = $value;
    }

    public function defaultValue($value): void
    {
        $this->defaultValue = $value;
    }

    public function enumValues(?array $value): void
    {
        $this->enumValues = $value;
    }

    public function size(?int $value): void
    {
        $this->size = $value;
    }

    public function precision(?int $value): void
    {
        $this->precision = $value;
    }

    public function scale(?int $value): void
    {
        $this->scale = $value;
    }

    public function primaryKey(bool $value): void
    {
        $this->isPrimaryKey = $value;
    }

    public function autoIncrement(bool $value): void
    {
        $this->autoIncrement = $value;
    }

    public function unsigned(bool $value): void
    {
        $this->unsigned = $value;
    }

    public function comment(?string $value): void
    {
        $this->comment = $value;
    }
}
