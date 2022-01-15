<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Schema;

use Exception;
use JsonException;
use PDO;
use PDOException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Dbal\Connection\ConnectionPdoInterface;
use Yiisoft\Dbal\Constraint\Constraint;
use Yiisoft\Dbal\Constraint\ConstraintInterface;
use Yiisoft\Dbal\Constraint\ForeignKeyConstraint;
use Yiisoft\Dbal\Constraint\IndexConstraint;
use Yiisoft\Dbal\Exception\InvalidConfigException;
use Yiisoft\Dbal\Exception\NotSupportedException;
use Yiisoft\Dbal\Schema\AbstractSchema;
use Yiisoft\Dbal\Schema\ColumnSchemaInterface;
use Yiisoft\Dbal\Schema\TableSchemaInterface;

/**
 * @psalm-type ColumnInfoArray = array{
 *   field: string,
 *   type: string,
 *   collation: string|null,
 *   null: string,
 *   key: string,
 *   default: string|null,
 *   extra: string,
 *   privileges: string,
 *   comment: string
 * }
 *
 * @psalm-type RowConstraint = array{
 *   constraint_name: string,
 *   column_name: string,
 *   referenced_table_name: string,
 *   referenced_column_name: string
 * }
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string
 *   }
 * >
 */
final class Schema extends AbstractSchema
{
    /**
     * @var ConnectionPdoInterface
     */
    protected $connection;

    /** @var array<array-key, string> $typeMap */
    private array $typeMap = [
        'tinyint' => self::TYPE_TINYINT,
        'bit' => self::TYPE_INTEGER,
        'smallint' => self::TYPE_SMALLINT,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'longblob' => self::TYPE_BINARY,
        'blob' => self::TYPE_BINARY,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'char' => self::TYPE_CHAR,
        'datetime' => self::TYPE_DATETIME,
        'year' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'enum' => self::TYPE_STRING,
        'varbinary' => self::TYPE_BINARY,
        'json' => self::TYPE_JSON,
    ];

    /**
     * @inheritDoc
     */
    protected function findTableNames(string $schema = ''): array
    {
        $sql = 'SHOW TABLES';

        if ($schema !== '') {
            $sql .= ' FROM ' . $this->connection->getQuoter()->quoteTableName($schema);
        }

        return $this->connection->createCommand($sql)->queryColumn();
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @throws Exception|Throwable
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    protected function loadTableSchema(string $name): ?TableSchemaInterface
    {
        $table = $this->resolveTableName($name);

        if ($this->findColumns($table)) {
            // @todo - rewrite query in function loadTableConstraints
//            $this->findConstraints($table);

            return $table;
        }

        return null;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Exception|Throwable if DB query fails.
     *
     * @return bool whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $tableName = $table->getFullName() ?? '';

        $sql = 'SHOW FULL COLUMNS FROM ' . $this->connection->getQuoter()->quoteTableName($tableName);

        try {
            $columns = $this->connection->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof PDOException && strpos($previous->getMessage(), 'SQLSTATE[42S02') !== false) {
                /**
                 * table does not exist.
                 *
                 * https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                 */
                return false;
            }

            throw $e;
        }

        $columns = $this->normalizePdoRowKeyCase($columns);

        /** @psalm-var ColumnInfoArray $info */
        foreach ($columns as $info) {
            $column = $this->loadColumnSchema($info);
            $table->columns($column->getName(), $column);

            if ($column->isPrimaryKey()) {
                $table->primaryKey($column->getName());
                if ($column->isAutoIncrement()) {
                    $table->sequenceName('');
                }
            }
        }

        return true;
    }

    /**
     * Loads the column information into a {@see ColumnSchemaInterface} object.
     *
     * @param array $info column information.
     *
     * @throws JsonException
     *
     * @return ColumnSchemaInterface the column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $column = $this->createColumnSchema();

        /** @psalm-var ColumnInfoArray $info */
        $column->name($info['field']);
        $column->allowNull($info['null'] === 'YES');
        $column->primaryKey(strpos($info['key'], 'PRI') !== false);
        $column->autoIncrement(stripos($info['extra'], 'auto_increment') !== false);
        $column->comment($info['comment']);
        $column->dbType($info['type']);
        $column->unsigned(stripos($info['type'], 'unsigned') !== false);

        $columnType = self::TYPE_STRING;
        if (preg_match('/^(\w+)(?:\(([^)]+)\))?/', $column->getDbType(), $matches)) {
            $type = strtolower($matches[1]);

            if (isset($this->typeMap[$type])) {
                $columnType = $this->typeMap[$type];
            }

            if (!empty($matches[2])) {
                if ($type === 'enum') {
                    preg_match_all("/'[^']*'/", $matches[2], $values);

                    foreach ($values[0] as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }

                    $column->enumValues($values);
                } else {
                    $values = explode(',', $matches[2]);
                    $column->precision((int) $values[0]);
                    $column->size((int) $values[0]);

                    if (isset($values[1])) {
                        $column->scale((int) $values[1]);
                    }

                    if ($type === 'tinyint' && $column->getSize() === 1) {
                        $columnType = self::TYPE_BOOLEAN;
                    } elseif ($type === 'bit') {
                        if ($column->getSize() > 32) {
                            $columnType = self::TYPE_BIGINT;
                        } elseif ($column->getSize() === 32) {
                            $columnType = self::TYPE_INTEGER;
                        }
                    }
                }
            }
        }
        // @todo implementation of convert to TypeInterface
//        $column->type($columnType);

//        $column->phpType($this->getColumnPhpType($column));

        if (!$column->isPrimaryKey()) {
            /**
             * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
             * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
             *
             * See details here: https://mariadb.com/kb/en/library/now/#description
             */
            if (
                ($column->getType() === 'timestamp' || $column->getType() === 'datetime')
                && preg_match('/^current_timestamp(?:\((\d*)\))?$/i', (string) $info['default'], $matches)
            ) {
//                $column->defaultValue(new Expression('CURRENT_TIMESTAMP' . (!empty($matches[1])
//                        ? '(' . $matches[1] . ')' : '')));
            } elseif (isset($type) && $type === 'bit') {
                $column->defaultValue(bindec(trim((string) $info['default'], 'b\'')));
//            } else {
//                $column->defaultValue($column->phpTypecast($info['default']));
            }
        }

        return $column;
    }


    /**
     * @inheritDoc
     */
    protected function loadTablePrimaryKey(string $tableName): ?Constraint
    {
        $tablePrimaryKey = $this->loadTableConstraints($tableName, ConstraintInterface::PRIMARY_KEY);

        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * @inheritDoc
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        $tableForeignKeys = $this->loadTableConstraints($tableName, ConstraintInterface::FOREIGN_KEYS);

        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * @inheritDoc
     */
    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<'SQL'
SELECT
    `s`.`INDEX_NAME` AS `name`,
    `s`.`COLUMN_NAME` AS `column_name`,
    `s`.`NON_UNIQUE` ^ 1 AS `index_is_unique`,
    `s`.`INDEX_NAME` = 'PRIMARY' AS `index_is_primary`
FROM `information_schema`.`STATISTICS` AS `s`
WHERE `s`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND `s`.`INDEX_SCHEMA` = `s`.`TABLE_SCHEMA` AND `s`.`TABLE_NAME` = :tableName
ORDER BY `s`.`SEQ_IN_INDEX` ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $indexes = $this->connection->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        /** @var array<array-key, array<array-key, mixed>> $indexes */
        $indexes = $this->normalizePdoRowKeyCase($indexes);
        $indexes = ArrayHelper::index($indexes, null, 'name');
        $result = [];

        /**
         * @psalm-var object|string|null $name
         * @psalm-var array<array-key, array<array-key, mixed>> $index
         */
        foreach ($indexes as $name => $index) {
            $ic = new IndexConstraint();

            $ic->primary((bool) $index[0]['index_is_primary']);
            $ic->unique((bool) $index[0]['index_is_unique']);
            $ic->name($name !== 'PRIMARY' ? $name : null);
            $ic->columnNames(ArrayHelper::getColumn($index, 'column_name'));

            $result[] = $ic;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function loadTableUniques(string $tableName): array
    {
        $tableUniques = $this->loadTableConstraints($tableName, ConstraintInterface::UNIQUES);

        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * @inheritDoc
     */
    protected function loadTableChecks(string $tableName): array
    {
        throw new NotSupportedException('MySQL does not support check constraints.');
    }

    /**
     * @inheritDoc
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException('MySQL does not support default value constraints.');
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return (Constraint|ForeignKeyConstraint)[]|Constraint|null constraints.
     *
     * @psalm-return Constraint|list<Constraint|ForeignKeyConstraint>|null
     */
    private function loadTableConstraints(string $tableName, string $returnType)
    {
        $sql = <<<'SQL'
SELECT
    `kcu`.`CONSTRAINT_NAME` AS `name`,
    `kcu`.`COLUMN_NAME` AS `column_name`,
    `tc`.`CONSTRAINT_TYPE` AS `type`,
    CASE
        WHEN :schemaName IS NULL AND `kcu`.`REFERENCED_TABLE_SCHEMA` = DATABASE() THEN NULL
        ELSE `kcu`.`REFERENCED_TABLE_SCHEMA`
    END AS `foreign_table_schema`,
    `kcu`.`REFERENCED_TABLE_NAME` AS `foreign_table_name`,
    `kcu`.`REFERENCED_COLUMN_NAME` AS `foreign_column_name`,
    `rc`.`UPDATE_RULE` AS `on_update`,
    `rc`.`DELETE_RULE` AS `on_delete`,
    `kcu`.`ORDINAL_POSITION` AS `position`
FROM
    `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`,
    `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc`,
    `information_schema`.`TABLE_CONSTRAINTS` AS `tc`
WHERE
    `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND `kcu`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND `kcu`.`TABLE_NAME` = :tableName
    AND `rc`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND `rc`.`TABLE_NAME` = :tableName AND `rc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME`
    AND `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND `tc`.`TABLE_NAME` = :tableName AND `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME` AND `tc`.`CONSTRAINT_TYPE` = 'FOREIGN KEY'
UNION
SELECT
    `kcu`.`CONSTRAINT_NAME` AS `name`,
    `kcu`.`COLUMN_NAME` AS `column_name`,
    `tc`.`CONSTRAINT_TYPE` AS `type`,
    NULL AS `foreign_table_schema`,
    NULL AS `foreign_table_name`,
    NULL AS `foreign_column_name`,
    NULL AS `on_update`,
    NULL AS `on_delete`,
    `kcu`.`ORDINAL_POSITION` AS `position`
FROM
    `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`,
    `information_schema`.`TABLE_CONSTRAINTS` AS `tc`
WHERE
    `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND `kcu`.`TABLE_NAME` = :tableName
    AND `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA` AND `tc`.`TABLE_NAME` = :tableName AND `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME` AND `tc`.`CONSTRAINT_TYPE` IN ('PRIMARY KEY', 'UNIQUE')
ORDER BY `position` ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $constraints = $this->connection->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->getSchemaName(),
                ':tableName' => $resolvedName->getName(),
            ]
        )->queryAll();

        /** @var array<array-key, array> $constraints */
        $constraints = $this->normalizePdoRowKeyCase($constraints);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            ConstraintInterface::PRIMARY_KEY => null,
            ConstraintInterface::FOREIGN_KEYS => [],
            ConstraintInterface::UNIQUES => [],
        ];

        /**
         * @var string $type
         * @var array $names
         */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PRIMARY KEY':
                        $ct = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));

                        $result[ConstraintInterface::PRIMARY_KEY] = $ct;

                        break;
                    case 'FOREIGN KEY':
                        $fk = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate($constraint[0]['on_update'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);

                        $result[ConstraintInterface::FOREIGN_KEYS][] = $fk;

                        break;
                    case 'UNIQUE':
                        $ct = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);

                        $result[ConstraintInterface::UNIQUES][] = $ct;

                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * Sets the metadata of the given type for the given table.
     *
     * @param string $name table name.
     * @param string $type metadata type.
     * @param mixed $data metadata.
     */
    private function setTableMetadata(string $name, string $type, $data): void
    {
        $this->tableMetadata[$this->getRawTableName($name)][$type] = $data;
    }

    /**
     * Changes row's array key case to lower if PDO's one is set to uppercase.
     *
     * @param array $row row's array or an array of row's arrays.
     *
     * @throws Exception
     *
     * @return array normalized row or rows.
     */
    protected function normalizePdoRowKeyCase(array $row): array
    {
        if ($this->connection->getPdo() === null || $this->connection->getPdo()->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_UPPER) {
            return $row;
        }

        return array_map(static function (array $row) {
            return array_change_key_case($row, CASE_LOWER);
        }, $row);
    }

    /**
     * @inheritDoc
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        $table = new TableSchema();

        $parts = explode('.', str_replace('`', '', $name));

//        if (isset($parts[1])) {
//            $table->schemaName($parts[0]);
//            $table->name($parts[1]);
//            $table->fullName((string) $table->getSchemaName() . '.' . (string) $table->getName());
//        } else {
            $table->name($parts[0]);
            $table->fullName($parts[0]);
//        }

        return $table;
    }

    /**
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     *
     * @return ColumnSchemaInterface column schema instance.
     */
    private function createColumnSchema(): ColumnSchemaInterface
    {
        return new ColumnSchema();
    }
}
