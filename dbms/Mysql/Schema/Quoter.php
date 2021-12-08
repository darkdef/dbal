<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Schema;

use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Schema\QuoterInterface;

final class Quoter implements QuoterInterface
{
    private ConnectionInterface $connection;

    private string $commonQuoteCharacter = '`';

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function quoteValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return "'" . preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]~u', '\\\$0', $value) . "'";
    }

    private function escapeString(string $str)
    {
        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex )
            $str = preg_replace( $regex, '', $str );
        $str = str_replace("'", "''", $str );
        return $str;
    }

    public function quoteColumnName(string $name): string
    {
        if (strpos($name, '[[') !== false) {
            return $name;
        }

        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        if (strpos($name, '{{') !== false) {
            return $name;
        }

        return $prefix . $this->quoteSimpleColumnName($name);
    }

    private function quoteSimpleColumnName(string $name): string
    {
        if ($name === '*' || strpos($name, $this->commonQuoteCharacter) !== false) {
            return $name;
        }

        return "{$this->commonQuoteCharacter}{$name}{$this->commonQuoteCharacter}";
    }

    /**
     * @todo Improve to Table(for caches in Schema)
     * @param string $name
     * @return string
     */
    public function quoteTableName($name): string
    {
        if (strpos($name, '{{') !== false) {
            return $name;
        }

        if (strpos($name, $this->commonQuoteCharacter) !== false) {
            return $name;
        }

        return "{$this->commonQuoteCharacter}{$name}{$this->commonQuoteCharacter}";
    }

    /**
     * @inheritDoc
     */
    public function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+%?)}}|\\[\\[([\w\-. ]+)]])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->connection->getTablePrefix(), $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }
}
