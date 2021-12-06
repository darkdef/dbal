<?php

declare(strict_types=1);

namespace Yiisoft\DbalMysql\Command;

use Yiisoft\Dbal\Command\CommandInterface;
use Yiisoft\Dbal\Command\ValueInterface;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Connection\ConnectionPdoInterface;

use Exception;
use PDO;
use PDOStatement;
use Yiisoft\Dbal\Type\IntType;
use Yiisoft\Dbal\Type\StringType;

final class Command implements CommandInterface
{
    private ConnectionPdoInterface $db;

    private ?string $sql;

    /**
     * @var ValueInterface[]
     */
    private array $params = [];

    private ?PDOStatement $pdoStatement = null;

    private int $fetchMode = PDO::FETCH_ASSOC;

    public function __construct(ConnectionInterface $db, ?string $sql = null, array $params = [])
    {
        if (!($db instanceof ConnectionPdoInterface)) {
            throw new Exception('Need instance of ConnectionPdoInterface');
        }

        $this->db = $db;
        if ($sql !== null) {
            $this->sql = $this->quoteSql($sql);
        }

        $this->bindValues($params);
    }

    /**
     * @todo need test
     */
    public function setSql(string $sql): CommandInterface
    {
        $this->sql = $this->quoteSql($sql);
        return $this;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @todo need test
     */
    public function setRawSql(string $sql): CommandInterface
    {
        $this->sql = $sql;
        return $this;
    }

    public function getRawSql(): string
    {
        if (empty($this->params)) {
            return $this->sql;
        }

        $params = [];
        foreach ($this->params as $name => $valueObject) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }

            $params[$name] = $valueObject->asString();
        }

        return strtr($this->sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function execute(): int
    {
        $sql = $this->getSql();

        if (empty($sql)) {
            throw new Exception('SQL query is empty');
        }

        $this->db->open();
        $pdo = $this->db->getPdo();

        $this->pdoStatement = $pdo->prepare($sql);

        $result = $this->pdoStatement->execute($this->getBindParams());

        if ($result) {
            return $this->pdoStatement->rowCount();
        }

        return 0;
    }

    public function bindValue($name, $value, ?string $dataType = ''): CommandInterface
    {
        $this->params[$name] = $this->getValueObject($value, $dataType);

        return $this;
    }

    public function bindValues(array $values): CommandInterface
    {
        foreach ($values as $name => $value) {
            if ($value instanceof ValueInterface) {
                $this->params[$name] = $value;
            } elseif (is_array($value)) {
                $this->bindValue($name, ...$value);
            } else {
                $this->bindValue($name, $value);
            }
        }

        return $this;
    }

    /**
     * @param mixed $value
     * @param string $dataType
     */
    private function getValueObject($value, string $dataType): ValueInterface
    {
        if (empty($dataType)) {
            $dataType = gettype($value);
        }

        if ($dataType === 'integer') {
            $type = new IntType();
        } else {
            $type = new StringType();
        }

        return new Value($value, $type);
    }

    private function getBindParams(): array
    {
        return array_map(static function(ValueInterface $value) {
            return $value->getValue();
        }, $this->params);
    }

    private function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+%?)}}|\\[\\[([\w\-. ]+)]])/',
            function ($matches) {
                return '`' . $matches[2] . '`';
//                if (isset($matches[3])) {
//                    return $this->quoteColumnName($matches[3]);
//                }

//                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    public function queryOne()
    {
        $this->execute();
        return $this->pdoStatement->fetch($this->fetchMode);
    }

    public function queryAll(): array
    {
        $this->execute();
        return $this->pdoStatement->fetchAll($this->fetchMode);
    }

    public function queryScalar()
    {
        $this->execute();
        $result = $this->pdoStatement->fetchColumn(0);

        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return $result;

    }

    public function queryColumn(): array
    {
        $this->execute();
        return $this->pdoStatement->fetchAll(PDO::FETCH_COLUMN);
    }
}
