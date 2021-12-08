<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

interface QuoterInterface
{
    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param int|string $value string to be quoted
     *
     * @return int|string the properly quoted string
     */
    public function quoteValue($value);

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{', then this
     * method will do nothing.
     *
     * @param string $name column name
     *
     * @return string the properly quoted column name
     */
    public function quoteColumnName(string $name): string;

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{', then this method
     * will do nothing.
     *
     * @param string $name table name
     *
     * @return string the properly quoted table name
     */
    public function quoteTableName(string $name): string;

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     *
     * Tokens enclosed within double curly brackets are treated as table names, while tokens enclosed within double
     * square brackets are column names. They will be quoted accordingly. Also, the percentage character "%" at the
     * beginning or ending of a table name will be replaced with {@see tablePrefix}.
     *
     * @param string $sql the SQL to be quoted
     *
     * @return string the quoted SQL
     */
    public function quoteSql(string $sql): string;
}
