<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Yiisoft\Dbal\Exception\InvalidArgumentException;

interface TableSchemaInterface
{
    /**
     * Gets the named column metadata.
     *
     * This is a convenient method for retrieving a named column even if it does not exist.
     *
     * @param string $name column name
     *
     * @return ColumnSchemaInterface|null metadata of the named column. Null if the named column does not exist.
     */
    public function getColumn(string $name): ?ColumnSchemaInterface;

    /**
     * Returns the names of all columns in this table.
     *
     * @return array list of column names
     */
    public function getColumnNames(): array;

    /**
     * Manually specifies the primary key for this table.
     *
     * @param array|string $keys the primary key (can be composite)
     *
     * @throws InvalidArgumentException if the specified key cannot be found in the table.
     */
    public function fixPrimaryKey($keys): void;

    /**
     * @return string|null the name of the schema that this table belongs to.
     */
    public function getSchemaName(): ?string;

    /**
     * @return string the name of this table. The schema name is not included. Use {@see fullName} to get the name with
     * schema name prefix.
     */
    public function getName(): string;

    /**
     * @return string|null the full name of this table, which includes the schema name prefix, if any. Note that if the
     * schema name is the same as the {@see SchemaInterface::defaultSchema|default schema name}, the schema name will not be
     * included.
     */
    public function getFullName(): ?string;

    /**
     * @return string|null sequence name for the primary key. Null if no sequence.
     */
    public function getSequenceName(): ?string;

    /**
     * @return array primary keys of this table.
     */
    public function getPrimaryKey(): array;

    /**
     * @return ColumnSchemaInterface[] column metadata of this table. Each array element is a {@see ColumnSchemaInterface} object, indexed
     * by column names.
     */
    public function getColumns(): array;

    public function schemaName(?string $value): void;

    public function name(string $value): void;

    public function fullName(?string $value): void;

    public function sequenceName(?string $value): void;

    public function primaryKey(string $value): void;

    public function columns(string $index, ColumnSchemaInterface $value): void;
}
