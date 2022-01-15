<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Yiisoft\Dbal\Type\TypeInterface;

interface ColumnSchemaInterface
{
    public function getType(): ?TypeInterface;

    public function type(TypeInterface $value): void;

    /**
     * @return string the DB type of this column. Possible DB types vary according to the type of DBMS.
     */
    public function getDbType(): string;

    public function dbType(string $value): void;

    /**
     * @return string name of this column (without quotes).
     */
    public function getName(): string;

    public function name(string $value): void;

    /**
     * @return bool whether this column can be null.
     */
    public function isAllowNull(): bool;

    public function allowNull(bool $value): void;

    /**
     * @return mixed default value of this column
     */
    public function getDefaultValue();

    public function defaultValue($value): void;

    /**
     * @return array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public function getEnumValues(): ?array;

    public function enumValues(?array $value): void;

    /**
     * @return int display size of the column.
     */
    public function getSize(): ?int;

    public function size(?int $value): void;

    /**
     * @return int precision of the column data, if it is numeric.
     */
    public function getPrecision(): ?int;

    public function precision(?int $value): void;

    /**
     * @return int scale of the column data, if it is numeric.
     */
    public function getScale(): ?int;

    public function scale(?int $value): void;

    /**
     * @return bool whether this column is a primary key
     */
    public function isPrimaryKey(): bool;

    public function primaryKey(bool $value): void;

    /**
     * @return bool whether this column is auto-incremental
     */
    public function isAutoIncrement(): bool;

    public function autoIncrement(bool $value): void;

    /**
     * @return bool whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     */
    public function isUnsigned(): bool;

    public function unsigned(bool $value): void;

    /**
     * @return string|null comment of this column. Not all DBMS support this.
     */
    public function getComment(): ?string;

    public function comment(?string $value): void;
}
