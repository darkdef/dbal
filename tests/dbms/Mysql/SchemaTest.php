<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Tests;

use PDO;
use Yiisoft\Dbal\Exception\Exception;
use Yiisoft\Dbal\Exception\InvalidConfigException;
use Yiisoft\Dbal\Exception\NotSupportedException;
//use Yiisoft\Dbal\Expression\Expression;
use Yiisoft\DbalMysql\Schema\ColumnSchema;
use Yiisoft\DbalMysql\Schema\Schema;
use Yiisoft\DbalMysql\Schema\TableSchema;

use function array_map;
use function trim;
use function version_compare;

/**
 * @group mysql
 */
final class SchemaTest extends TestCase
{
    public function testGetTableSchemasWithAttrCase(): void
    {
        $db = $this->getConnection(false);
        $db->open();

        $db->getPdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());

        $db->getPdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());
    }
//
//    public function testGetNonExistingTableSchema(): void
//    {
//        $this->assertNull($this->getConnection()->getSchema()->getTableSchema('nonexisting_table'));
//    }
//
//    public function testSchemaCache(): void
//    {
//        $db = $this->getConnection();
//
//        $schema = $db->getSchema();
//
//        $noCacheTable = $schema->getTableSchema('type', true);
//        $cachedTable = $schema->getTableSchema('type', false);
//
//        $this->assertEquals($noCacheTable, $cachedTable);
//
//        $db->createCommand('')->renameTable('type', 'type_test');
//
//        $noCacheTable = $schema->getTableSchema('type', true);
//
//        $this->assertNotSame($noCacheTable, $cachedTable);
//
//        $db->createCommand()->renameTable('type_test', 'type');
//    }
//
//    /**
//     * @depends testSchemaCache
//     */
//    public function testRefreshTableSchema(): void
//    {
//        $schema = $this->getConnection()->getSchema();
//
//        $noCacheTable = $schema->getTableSchema('type', true);
//
//        $schema->refreshTableSchema('type');
//
//        $refreshedTable = $schema->getTableSchema('type', false);
//
//        $this->assertNotSame($noCacheTable, $refreshedTable);
//    }
//
//    public function testCompositeFk(): void
//    {
//        $schema = $this->getConnection()->getSchema();
//
//        $table = $schema->getTableSchema('composite_fk');
//
//        $fk = $table->getForeignKeys();
//
//        $this->assertCount(1, $fk);
//        $this->assertTrue(isset($fk['FK_composite_fk_order_item']));
//        $this->assertEquals('order_item', $fk['FK_composite_fk_order_item'][0]);
//        $this->assertEquals('order_id', $fk['FK_composite_fk_order_item']['order_id']);
//        $this->assertEquals('item_id', $fk['FK_composite_fk_order_item']['item_id']);
//    }
//
//    public function testGetPDOType(): void
//    {
//        $values = [
//            [null, PDO::PARAM_NULL],
//            ['', PDO::PARAM_STR],
//            ['hello', PDO::PARAM_STR],
//            [0, PDO::PARAM_INT],
//            [1, PDO::PARAM_INT],
//            [1337, PDO::PARAM_INT],
//            [true, PDO::PARAM_BOOL],
//            [false, PDO::PARAM_BOOL],
//            [$fp = fopen(__FILE__, 'rb'), PDO::PARAM_LOB],
//        ];
//
//        $schema = $this->getConnection()->getSchema();
//
//        foreach ($values as $value) {
//            $this->assertEquals($value[1], $schema->getPdoType($value[0]), 'type for value ' . print_r($value[0], true) . ' does not match.');
//        }
//
//        fclose($fp);
//    }
//
//    public function testColumnSchema(): void
//    {
//        $columns = $this->getExpectedColumns();
//
//        $table = $this->getConnection(false)->getSchema()->getTableSchema('type', true);
//
//        $expectedColNames = array_keys($columns);
//
//        sort($expectedColNames);
//
//        $colNames = $table->getColumnNames();
//
//        sort($colNames);
//
//        $this->assertEquals($expectedColNames, $colNames);
//
//        foreach ($table->getColumns() as $name => $column) {
//            $expected = $columns[$name];
//            $this->assertSame(
//                $expected['dbType'],
//                $column->getDbType(),
//                "dbType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
//            );
//            $this->assertSame(
//                $expected['phpType'],
//                $column->getPhpType(),
//                "phpType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
//            );
//            $this->assertSame($expected['type'], $column->getType(), "type of column $name does not match.");
//            $this->assertSame(
//                $expected['allowNull'],
//                $column->isAllowNull(),
//                "allowNull of column $name does not match."
//            );
//            $this->assertSame(
//                $expected['autoIncrement'],
//                $column->isAutoIncrement(),
//                "autoIncrement of column $name does not match."
//            );
//            $this->assertSame(
//                $expected['enumValues'],
//                $column->getEnumValues(),
//                "enumValues of column $name does not match."
//            );
//            $this->assertSame($expected['size'], $column->getSize(), "size of column $name does not match.");
//            $this->assertSame(
//                $expected['precision'],
//                $column->getPrecision(),
//                "precision of column $name does not match."
//            );
//            $this->assertSame($expected['scale'], $column->getScale(), "scale of column $name does not match.");
//            if (\is_object($expected['defaultValue'])) {
//                $this->assertIsObject(
//                    $column->getDefaultValue(),
//                    "defaultValue of column $name is expected to be an object but it is not."
//                );
//                $this->assertEquals(
//                    (string) $expected['defaultValue'],
//                    (string) $column->getDefaultValue(),
//                    "defaultValue of column $name does not match."
//                );
//            } else {
//                $this->assertEquals(
//                    $expected['defaultValue'],
//                    $column->getDefaultValue(),
//                    "defaultValue of column $name does not match."
//                );
//            }
//            /* Pgsql only */
//            if (isset($expected['dimension'])) {
//                $this->assertSame(
//                    $expected['dimension'],
//                    $column->getDimension(),
//                    "dimension of column $name does not match"
//                );
//            }
//        }
//    }
//
//    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
//    {
//        $columnSchema = new ColumnSchema();
//
//        $columnSchema->setType(Schema::TYPE_CHAR);
//
//        $this->assertSame('', $columnSchema->dbTypecast(''));
//    }
//
//    public function testNegativeDefaultValues(): void
//    {
//        $schema = $this->getConnection()->getSchema();
//
//        $table = $schema->getTableSchema('negative_default_values');
//
//        $this->assertEquals(-123, $table->getColumn('tinyint_col')->getDefaultValue());
//        $this->assertEquals(-123, $table->getColumn('smallint_col')->getDefaultValue());
//        $this->assertEquals(-123, $table->getColumn('int_col')->getDefaultValue());
//        $this->assertEquals(-123, $table->getColumn('bigint_col')->getDefaultValue());
//        $this->assertEquals(-12345.6789, $table->getColumn('float_col')->getDefaultValue());
//        $this->assertEquals(-33.22, $table->getColumn('numeric_col')->getDefaultValue());
//    }
//
//    public function testContraintTablesExistance(): void
//    {
//        $tableNames = [
//            'T_constraints_1',
//            'T_constraints_2',
//            'T_constraints_3',
//            'T_constraints_4',
//        ];
//
//        $schema = $this->getConnection()->getSchema();
//
//        foreach ($tableNames as $tableName) {
//            $tableSchema = $schema->getTableSchema($tableName);
//            $this->assertInstanceOf(TableSchema::class, $tableSchema, $tableName);
//        }
//    }
//
//    public function testGetColumnNoExist(): void
//    {
//        $schema = $this->getConnection()->getSchema();
//        $table = $schema->getTableSchema('negative_default_values');
//
//        $this->assertNull($table->getColumn('no_exist'));
//    }
//
//    private function assertMetadataEquals($expected, $actual): void
//    {
//        switch (strtolower(gettype($expected))) {
//            case 'object':
//                $this->assertIsObject($actual);
//                break;
//            case 'array':
//                $this->assertIsArray($actual);
//                break;
//            case 'null':
//                $this->assertNull($actual);
//                break;
//        }
//
//        if (is_array($expected)) {
//            $this->normalizeArrayKeys($expected, false);
//            $this->normalizeArrayKeys($actual, false);
//        }
//
//        $this->normalizeConstraints($expected, $actual);
//
//        if (is_array($expected)) {
//            $this->normalizeArrayKeys($expected, true);
//            $this->normalizeArrayKeys($actual, true);
//        }
//
//        $this->assertEquals($expected, $actual);
//    }
//
//    private function normalizeArrayKeys(array &$array, bool $caseSensitive): void
//    {
//        $newArray = [];
//
//        foreach ($array as $value) {
//            if ($value instanceof Constraint) {
//                $key = (array) $value;
//                unset(
//                    $key["\000Yiisoft\Db\Constraint\Constraint\000name"],
//                    $key["\u0000Yiisoft\\Db\\Constraint\\ForeignKeyConstraint\u0000foreignSchemaName"]
//                );
//
//                foreach ($key as $keyName => $keyValue) {
//                    if ($keyValue instanceof AnyCaseValue) {
//                        $key[$keyName] = $keyValue->value;
//                    } elseif ($keyValue instanceof AnyValue) {
//                        $key[$keyName] = '[AnyValue]';
//                    }
//                }
//
//                ksort($key, SORT_STRING);
//
//                $newArray[$caseSensitive
//                    ? json_encode($key, JSON_THROW_ON_ERROR)
//                    : strtolower(json_encode($key, JSON_THROW_ON_ERROR))] = $value;
//            } else {
//                $newArray[] = $value;
//            }
//        }
//
//        ksort($newArray, SORT_STRING);
//
//        $array = $newArray;
//    }
//
//    private function normalizeConstraints(&$expected, &$actual): void
//    {
//        if (is_array($expected)) {
//            foreach ($expected as $key => $value) {
//                if (!$value instanceof Constraint || !isset($actual[$key]) || !$actual[$key] instanceof Constraint) {
//                    continue;
//                }
//
//                $this->normalizeConstraintPair($value, $actual[$key]);
//            }
//        } elseif ($expected instanceof Constraint && $actual instanceof Constraint) {
//            $this->normalizeConstraintPair($expected, $actual);
//        }
//    }
//
//    private function normalizeConstraintPair(Constraint $expectedConstraint, Constraint $actualConstraint): void
//    {
//        if (get_class($expectedConstraint) !== get_class($actualConstraint)) {
//            return;
//        }
//
//        foreach (array_keys((array) $expectedConstraint) as $name) {
//            if ($expectedConstraint->getName() instanceof AnyValue) {
//                $actualConstraint->name($expectedConstraint->getName());
//            } elseif ($expectedConstraint->getName() instanceof AnyCaseValue) {
//                $actualConstraint->name(new AnyCaseValue($actualConstraint->getName()));
//            }
//        }
//    }
//
//    public function constraintsProviderTrait(): array
//    {
//        return [
//            '1: primary key' => [
//                'T_constraints_1',
//                'primaryKey',
//                (new Constraint())
//                    ->name(AnyValue::getInstance())
//                    ->columnNames(['C_id']),
//            ],
//            '1: check' => [
//                'T_constraints_1',
//                'checks',
//                [
//                    (new CheckConstraint())
//                        ->name(AnyValue::getInstance())
//                        ->columnNames(['C_check'])
//                        ->expression("C_check <> ''"),
//                ],
//            ],
//            '1: unique' => [
//                'T_constraints_1',
//                'uniques',
//                [
//                    (new Constraint())
//                        ->name('CN_unique')
//                        ->columnNames(['C_unique']),
//                ],
//            ],
//            '1: index' => [
//                'T_constraints_1',
//                'indexes',
//                [
//                    (new IndexConstraint())
//                        ->name(AnyValue::getInstance())
//                        ->columnNames(['C_id'])
//                        ->unique(true)
//                        ->primary(true),
//                    (new IndexConstraint())
//                        ->name('CN_unique')
//                        ->columnNames(['C_unique'])
//                        ->primary(false)
//                        ->unique(true),
//                ],
//            ],
//            '1: default' => ['T_constraints_1', 'defaultValues', false],
//
//            '2: primary key' => [
//                'T_constraints_2',
//                'primaryKey',
//                (new Constraint())
//                    ->name('CN_pk')
//                    ->columnNames(['C_id_1', 'C_id_2']),
//            ],
//            '2: unique' => [
//                'T_constraints_2',
//                'uniques',
//                [
//                    (new Constraint())
//                        ->name('CN_constraints_2_multi')
//                        ->columnNames(['C_index_2_1', 'C_index_2_2']),
//                ],
//            ],
//            '2: index' => [
//                'T_constraints_2',
//                'indexes',
//                [
//                    (new IndexConstraint())
//                        ->name(AnyValue::getInstance())
//                        ->columnNames(['C_id_1', 'C_id_2'])
//                        ->unique(true)
//                        ->primary(true),
//                    (new IndexConstraint())
//                        ->name('CN_constraints_2_single')
//                        ->columnNames(['C_index_1'])
//                        ->primary(false)
//                        ->unique(false),
//                    (new IndexConstraint())
//                        ->name('CN_constraints_2_multi')
//                        ->columnNames(['C_index_2_1', 'C_index_2_2'])
//                        ->primary(false)
//                        ->unique(true),
//                ],
//            ],
//            '2: check' => ['T_constraints_2', 'checks', []],
//            '2: default' => ['T_constraints_2', 'defaultValues', false],
//
//            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
//            '3: foreign key' => [
//                'T_constraints_3',
//                'foreignKeys',
//                [
//                    (new ForeignKeyConstraint())
//                        ->name('CN_constraints_3')
//                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
//                        ->foreignTableName('T_constraints_2')
//                        ->foreignColumnNames(['C_id_1', 'C_id_2'])
//                        ->onDelete('CASCADE')
//                        ->onUpdate('CASCADE'),
//                ],
//            ],
//            '3: unique' => ['T_constraints_3', 'uniques', []],
//            '3: index' => [
//                'T_constraints_3',
//                'indexes',
//                [
//                    (new IndexConstraint())
//                        ->name('CN_constraints_3')
//                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
//                        ->unique(false)
//                        ->primary(false),
//                ],
//            ],
//            '3: check' => ['T_constraints_3', 'checks', []],
//            '3: default' => ['T_constraints_3', 'defaultValues', false],
//
//            '4: primary key' => [
//                'T_constraints_4',
//                'primaryKey',
//                (new Constraint())
//                    ->name(AnyValue::getInstance())
//                    ->columnNames(['C_id']),
//            ],
//            '4: unique' => [
//                'T_constraints_4',
//                'uniques',
//                [
//                    (new Constraint())
//                        ->name('CN_constraints_4')
//                        ->columnNames(['C_col_1', 'C_col_2']),
//                ],
//            ],
//            '4: check' => ['T_constraints_4', 'checks', []],
//            '4: default' => ['T_constraints_4', 'defaultValues', false],
//        ];
//    }
//
//    public function pdoAttributesProviderTrait(): array
//    {
//        return [
//            [[PDO::ATTR_EMULATE_PREPARES => true]],
//            [[PDO::ATTR_EMULATE_PREPARES => false]],
//        ];
//    }
//
//    public function tableSchemaCachePrefixesProviderTrait(): array
//    {
//        $configs = [
//            [
//                'prefix' => '',
//                'name' => 'type',
//            ],
//            [
//                'prefix' => '',
//                'name' => '{{%type}}',
//            ],
//            [
//                'prefix' => 'ty',
//                'name' => '{{%pe}}',
//            ],
//        ];
//
//        $data = [];
//        foreach ($configs as $config) {
//            foreach ($configs as $testConfig) {
//                if ($config === $testConfig) {
//                    continue;
//                }
//
//                $description = sprintf(
//                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
//                    $config['name'],
//                    $config['prefix'],
//                    $testConfig['name'],
//                    $testConfig['prefix']
//                );
//                $data[$description] = [
//                    $config['prefix'],
//                    $config['name'],
//                    $testConfig['prefix'],
//                    $testConfig['name'],
//                ];
//            }
//        }
//
//        return $data;
//    }
//
//    public function lowercaseConstraintsProviderTrait(): array
//    {
//        return $this->constraintsProvider();
//    }
//
//    public function uppercaseConstraintsProviderTrait(): array
//    {
//        return $this->constraintsProvider();
//    }
//
//    public function getExpectedColumns(): array
//    {
//        $version = $this->getConnection()->getServerVersion();
//
//        return [
//            'int_col' => [
//                'type' => 'integer',
//                'dbType' => version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
//                'phpType' => 'integer',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => version_compare($version, '8.0.17', '>') ? null : 11,
//                'precision' => version_compare($version, '8.0.17', '>') ? null : 11,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'int_col2' => [
//                'type' => 'integer',
//                'dbType' => version_compare($version, '8.0.17', '>') ? 'int' : 'int(11)',
//                'phpType' => 'integer',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => version_compare($version, '8.0.17', '>') ? null : 11,
//                'precision' => version_compare($version, '8.0.17', '>') ? null : 11,
//                'scale' => null,
//                'defaultValue' => 1,
//            ],
//            'tinyint_col' => [
//                'type' => 'tinyint',
//                'dbType' => version_compare($version, '8.0.17', '>') ? 'tinyint' : 'tinyint(3)',
//                'phpType' => 'integer',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => version_compare($version, '8.0.17', '>') ? null : 3,
//                'precision' => version_compare($version, '8.0.17', '>') ? null : 3,
//                'scale' => null,
//                'defaultValue' => 1,
//            ],
//            'smallint_col' => [
//                'type' => 'smallint',
//                'dbType' => version_compare($version, '8.0.17', '>') ? 'smallint' : 'smallint(1)',
//                'phpType' => 'integer',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => version_compare($version, '8.0.17', '>') ? null : 1,
//                'precision' => version_compare($version, '8.0.17', '>') ? null : 1,
//                'scale' => null,
//                'defaultValue' => 1,
//            ],
//            'char_col' => [
//                'type' => 'char',
//                'dbType' => 'char(100)',
//                'phpType' => 'string',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 100,
//                'precision' => 100,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'char_col2' => [
//                'type' => 'string',
//                'dbType' => 'varchar(100)',
//                'phpType' => 'string',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 100,
//                'precision' => 100,
//                'scale' => null,
//                'defaultValue' => 'something',
//            ],
//            'char_col3' => [
//                'type' => 'text',
//                'dbType' => 'text',
//                'phpType' => 'string',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'enum_col' => [
//                'type' => 'string',
//                'dbType' => "enum('a','B','c,D')",
//                'phpType' => 'string',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => ['a', 'B', 'c,D'],
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'float_col' => [
//                'type' => 'double',
//                'dbType' => 'double(4,3)',
//                'phpType' => 'double',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 4,
//                'precision' => 4,
//                'scale' => 3,
//                'defaultValue' => null,
//            ],
//            'float_col2' => [
//                'type' => 'double',
//                'dbType' => 'double',
//                'phpType' => 'double',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => 1.23,
//            ],
//            'blob_col' => [
//                'type' => 'binary',
//                'dbType' => 'blob',
//                'phpType' => 'resource',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'numeric_col' => [
//                'type' => 'decimal',
//                'dbType' => 'decimal(5,2)',
//                'phpType' => 'string',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 5,
//                'precision' => 5,
//                'scale' => 2,
//                'defaultValue' => '33.22',
//            ],
//            'time' => [
//                'type' => 'timestamp',
//                'dbType' => 'timestamp',
//                'phpType' => 'string',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => '2002-01-01 00:00:00',
//            ],
//            'bool_col' => [
//                'type' => 'boolean',
//                'dbType' => 'tinyint(1)',
//                'phpType' => 'boolean',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 1,
//                'precision' => 1,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//            'bool_col2' => [
//                'type' => 'boolean',
//                'dbType' => 'tinyint(1)',
//                'phpType' => 'boolean',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 1,
//                'precision' => 1,
//                'scale' => null,
//                'defaultValue' => 1,
//            ],
//            'ts_default' => [
//                'type' => 'timestamp',
//                'dbType' => 'timestamp',
//                'phpType' => 'string',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
//            ],
//            'bit_col' => [
//                'type' => 'integer',
//                'dbType' => 'bit(8)',
//                'phpType' => 'integer',
//                'allowNull' => false,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => 8,
//                'precision' => 8,
//                'scale' => null,
//                'defaultValue' => 130, // b'10000010'
//            ],
//            'json_col' => [
//                'type' => 'json',
//                'dbType' => 'json',
//                'phpType' => 'array',
//                'allowNull' => true,
//                'autoIncrement' => false,
//                'enumValues' => null,
//                'size' => null,
//                'precision' => null,
//                'scale' => null,
//                'defaultValue' => null,
//            ],
//        ];
//    }
//
//    public function testLoadDefaultDatetimeColumn(): void
//    {
//        if (!version_compare($this->getConnection()->getServerVersion(), '5.6', '>=')) {
//            $this->markTestSkipped('Default datetime columns are supported since MySQL 5.6.');
//        }
//
//        $sql = <<<SQL
//CREATE TABLE  IF NOT EXISTS `datetime_test`  (
//  `id` int(11) NOT NULL AUTO_INCREMENT,
//  `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
//  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//  PRIMARY KEY (`id`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8
//SQL;
//
//        $this->getConnection()->createCommand($sql)->execute();
//
//        $schema = $this->getConnection()->getTableSchema('datetime_test');
//
//        $dt = $schema->getColumn('dt');
//
//        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
//        $this->assertEquals('CURRENT_TIMESTAMP', (string) $dt->getDefaultValue());
//    }
//
//    public function testDefaultDatetimeColumnWithMicrosecs(): void
//    {
//        if (!version_compare($this->getConnection()->getServerVersion(), '5.6.4', '>=')) {
//            $this->markTestSkipped(
//                'CURRENT_TIMESTAMP with microseconds as default column value is supported since MySQL 5.6.4.'
//            );
//        }
//
//        $sql = <<<SQL
//CREATE TABLE  IF NOT EXISTS `current_timestamp_test`  (
//  `dt` datetime(2) NOT NULL DEFAULT CURRENT_TIMESTAMP(2),
//  `ts` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8
//SQL;
//
//        $this->getConnection()->createCommand($sql)->execute();
//
//        $schema = $this->getConnection()->getTableSchema('current_timestamp_test');
//
//        $dt = $schema->getColumn('dt');
//
//        $this->assertInstanceOf(Expression::class, $dt->getDefaultValue());
//        $this->assertEquals('CURRENT_TIMESTAMP(2)', (string) $dt->getDefaultValue());
//
//        $ts = $schema->getColumn('ts');
//
//        $this->assertInstanceOf(Expression::class, $ts->getDefaultValue());
//        $this->assertEquals('CURRENT_TIMESTAMP(3)', (string) $ts->getDefaultValue());
//    }
//
//    /**
//     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed as
//     * CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
//     *
//     * {@see https://mariadb.com/kb/en/library/now/#description}
//     * {@see https://github.com/yiisoft/yii2/issues/15167}
//     */
//    public function testAlternativeDisplayOfDefaultCurrentTimestampInMariaDB(): void
//    {
//        /**
//         * We do not have a real database MariaDB >= 10.2.3 for tests, so we emulate the information that database
//         * returns in response to the query `SHOW FULL COLUMNS FROM ...`
//         */
//        $schema = new Schema($this->getConnection(), $this->schemaCache);
//
//        $column = $this->invokeMethod($schema, 'loadColumnSchema', [[
//            'field' => 'emulated_MariaDB_field',
//            'type' => 'timestamp',
//            'collation' => null,
//            'null' => 'NO',
//            'key' => '',
//            'default' => 'current_timestamp()',
//            'extra' => '',
//            'privileges' => 'select,insert,update,references',
//            'comment' => '',
//        ]]);
//
//        $this->assertInstanceOf(ColumnSchema::class, $column);
//        $this->assertInstanceOf(Expression::class, $column->getDefaultValue());
//        $this->assertEquals('CURRENT_TIMESTAMP', $column->getDefaultValue());
//    }
//
//    /**
//     * @dataProvider pdoAttributesProviderTrait
//     *
//     * @param array $pdoAttributes
//     *
//     * @throws Exception
//     * @throws InvalidConfigException
//     */
//    public function testGetTableNames(array $pdoAttributes): void
//    {
//        $connection = $this->getConnection(true);
//
//        foreach ($pdoAttributes as $name => $value) {
//            $connection->getPDO()->setAttribute($name, $value);
//        }
//
//        $schema = $connection->getSchema();
//
//        $tables = $schema->getTableNames();
//
//        if ($connection->getDriverName() === 'sqlsrv') {
//            $tables = array_map(static function ($item) {
//                return trim($item, '[]');
//            }, $tables);
//        }
//
//        $this->assertContains('customer', $tables);
//        $this->assertContains('category', $tables);
//        $this->assertContains('item', $tables);
//        $this->assertContains('order', $tables);
//        $this->assertContains('order_item', $tables);
//        $this->assertContains('type', $tables);
//        $this->assertContains('animal', $tables);
//        $this->assertContains('animal_view', $tables);
//    }
//
//    public function constraintsProvider()
//    {
//        $result = $this->constraintsProviderTrait();
//
//        $result['1: check'][2] = false;
//
//        $result['2: primary key'][2]->name(null);
//
//        $result['2: check'][2] = false;
//
//        $result['3: check'][2] = false;
//
//        $result['4: check'][2] = false;
//
//        return $result;
//    }
//
//    /**
//     * @dataProvider constraintsProvider
//     *
//     * @param string $tableName
//     * @param string $type
//     * @param mixed $expected
//     */
//    public function testTableSchemaConstraints(string $tableName, string $type, $expected): void
//    {
//        if ($expected === false) {
//            $this->expectException(NotSupportedException::class);
//        }
//
//        $constraints = $this->getConnection()->getSchema()->{'getTable' . ucfirst($type)}($tableName);
//
//        $this->assertMetadataEquals($expected, $constraints);
//    }
//
//    /**
//     * @dataProvider lowercaseConstraintsProviderTrait
//     *
//     * @param string $tableName
//     * @param string $type
//     * @param mixed $expected
//     *
//     * @throws Exception
//     * @throws InvalidConfigException
//     */
//    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, $expected): void
//    {
//        if ($expected === false) {
//            $this->expectException(NotSupportedException::class);
//        }
//
//        $connection = $this->getConnection();
//
//        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
//
//        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
//
//        $this->assertMetadataEquals($expected, $constraints);
//    }
//
//    /**
//     * @dataProvider uppercaseConstraintsProviderTrait
//     *
//     * @param string $tableName
//     * @param string $type
//     * @param mixed $expected
//     *
//     * @throws Exception
//     * @throws InvalidConfigException
//     */
//    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, $expected): void
//    {
//        if ($expected === false) {
//            $this->expectException(NotSupportedException::class);
//        }
//
//        $connection = $this->getConnection();
//
//        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
//
//        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
//
//        $this->assertMetadataEquals($expected, $constraints);
//    }
//
//    /**
//     * @depends testSchemaCache
//     *
//     * @dataProvider tableSchemaCachePrefixesProviderTrait
//     *
//     * @param string $tablePrefix
//     * @param string $tableName
//     * @param string $testTablePrefix
//     * @param string $testTableName
//     */
//    public function testTableSchemaCacheWithTablePrefixes(
//        string $tablePrefix,
//        string $tableName,
//        string $testTablePrefix,
//        string $testTableName
//    ): void {
//        $db = $this->getConnection();
//        $schema = $this->getConnection()->getSchema();
//
//        $db->setTablePrefix($tablePrefix);
//
//        $noCacheTable = $schema->getTableSchema($tableName, true);
//
//        $this->assertInstanceOf(TableSchema::class, $noCacheTable);
//
//        /* Compare */
//        $db->setTablePrefix($testTablePrefix);
//
//        $testNoCacheTable = $schema->getTableSchema($testTableName);
//
//        $this->assertSame($noCacheTable, $testNoCacheTable);
//
//        $db->setTablePrefix($tablePrefix);
//
//        $schema->refreshTableSchema($tableName);
//
//        $refreshedTable = $schema->getTableSchema($tableName, false);
//
//        $this->assertInstanceOf(TableSchema::class, $refreshedTable);
//        $this->assertNotSame($noCacheTable, $refreshedTable);
//
//        /* Compare */
//        $db->setTablePrefix($testTablePrefix);
//
//        $schema->refreshTableSchema($testTablePrefix);
//
//        $testRefreshedTable = $schema->getTableSchema($testTableName, false);
//
//        $this->assertInstanceOf(TableSchema::class, $testRefreshedTable);
//        $this->assertEquals($refreshedTable, $testRefreshedTable);
//        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);
//    }
//
//    public function testFindUniqueIndexes(): void
//    {
//        $db = $this->getConnection();
//
//        try {
//            $db->createCommand()->dropTable('uniqueIndex')->execute();
//        } catch (Exception $e) {
//        }
//
//        $db->createCommand()->createTable('uniqueIndex', [
//            'somecol' => 'string',
//            'someCol2' => 'string',
//        ])->execute();
//
//        /* @var $schema Schema */
//        $schema = $db->getSchema();
//
//        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
//        $this->assertEquals([], $uniqueIndexes);
//
//        $db->createCommand()->createIndex('somecolUnique', 'uniqueIndex', 'somecol', true)->execute();
//
//        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
//        $this->assertEquals([
//            'somecolUnique' => ['somecol'],
//        ], $uniqueIndexes);
//
//        // create another column with upper case letter that fails postgres
//        // see https://github.com/yiisoft/yii2/issues/10613
//        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();
//
//        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
//        $this->assertEquals([
//            'somecolUnique' => ['somecol'],
//            'someCol2Unique' => ['someCol2'],
//        ], $uniqueIndexes);
//
//        // see https://github.com/yiisoft/yii2/issues/13814
//        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol2', true)->execute();
//
//        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
//        $this->assertEquals([
//            'somecolUnique' => ['somecol'],
//            'someCol2Unique' => ['someCol2'],
//            'another unique index' => ['someCol2'],
//        ], $uniqueIndexes);
//    }
}
