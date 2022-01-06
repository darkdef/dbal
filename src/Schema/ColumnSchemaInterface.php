<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Yiisoft\Dbal\Type\TypeInterface;

interface ColumnSchemaInterface
{
    public function getType(): ?TypeInterface;

    public function type(TypeInterface $value): void;

    /**
     * @return string name of this column (without quotes).
     */
    public function getName(): string;

    /**
     * @return bool whether this column can be null.
     */
    public function isAllowNull(): bool;

    /**
     * @return mixed default value of this column
     */
    public function getDefaultValue();

    /**
     * @return array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public function getEnumValues(): ?array;

    /**
     * @return int display size of the column.
     */
    public function getSize(): ?int;

    /**
     * @return int precision of the column data, if it is numeric.
     */
    public function getPrecision(): ?int;

    /**
     * @return int scale of the column data, if it is numeric.
     */
    public function getScale(): ?int;

    /**
     * @return bool whether this column is a primary key
     */
    public function isPrimaryKey(): bool;

    /**
     * @return bool whether this column is auto-incremental
     */
    public function isAutoIncrement(): bool;

    /**
     * @return bool whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     */
    public function isUnsigned(): bool;

    /**
     * @return string|null comment of this column. Not all DBMS support this.
     */
    public function getComment(): ?string;

    public function name(string $value): void;

    public function allowNull(bool $value): void;

    public function defaultValue($value): void;

    public function enumValues(?array $value): void;

    public function size(?int $value): void;

    public function precision(?int $value): void;

    public function scale(?int $value): void;

    public function primaryKey(bool $value): void;

    public function autoIncrement(bool $value): void;

    public function unsigned(bool $value): void;

    public function comment(?string $value): void;
}
