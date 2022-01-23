<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Yiisoft\Dbal\Exception\InvalidArgumentException;

use function array_keys;

/**
 * TableSchema represents the metadata of a database table.
 *
 * @property array $columnNames List of column names. This property is read-only.
 */
abstract class AbstractTableSchema implements TableSchemaInterface
{
    private ?string $schemaName = null;
    private string $name = '';
    private ?string $fullName = null;
    private ?string $sequenceName = null;
    private array $primaryKey = [];
    private array $columns = [];
    private array $foreignKeys = [];

    /**
     * @inheritDoc
     */
    public function getColumn(string $name): ?ColumnSchemaInterface
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @inheritDoc
     */
    public function fixPrimaryKey($keys): void
    {
        $keys = (array) $keys;
        $this->primaryKey = $keys;

        foreach ($this->columns as $column) {
            $column->primaryKey(false);
        }

        foreach ($keys as $key) {
            if (isset($this->columns[$key])) {
                $this->columns[$key]->primaryKey(true);
            } else {
                throw new InvalidArgumentException("Primary key '$key' cannot be found in table '$this->name'.");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @inheritDoc
     */
    public function getSequenceName(): ?string
    {
        return $this->sequenceName;
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * @inheritDoc
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function schemaName(?string $value): void
    {
        $this->schemaName = $value;
    }

    public function name(string $value): void
    {
        $this->name = $value;
    }

    public function fullName(?string $value): void
    {
        $this->fullName = $value;
    }

    public function sequenceName(?string $value): void
    {
        $this->sequenceName = $value;
    }

    public function primaryKey(string $value): void
    {
        $this->primaryKey[] = $value;
    }

    public function columns(string $index, ColumnSchemaInterface $value): void
    {
        $this->columns[$index] = $value;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function compositeFK(int $id, string $from, string $to): void
    {
        $this->foreignKeys[$id][$from] = $to;
    }

    public function foreignKey(string $id, array $to): void
    {
        $this->foreignKeys[$id] = $to;
    }

    public function foreignKeys(array $value): void
    {
        $this->foreignKeys = $value;
    }
}
