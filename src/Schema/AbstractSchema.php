<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Throwable;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Dbal\Cache\SchemaCache;
use Yiisoft\Dbal\Connection\ConnectionInterface;
use Yiisoft\Dbal\Constraint\ConstraintFinder;
use Yiisoft\Dbal\Constraint\ConstraintFinderInterface;
use Yiisoft\Dbal\Exception\NotSupportedException;

abstract class AbstractSchema extends ConstraintFinder implements SchemaInterface
{
    /**
     * Schema cache version, to detect incompatibilities in cached values when the data format of the cache changes.
     */
    protected const SCHEMA_CACHE_VERSION = 1;

    protected ConnectionInterface $connection;
    protected ?SchemaCache $schemaCache;

    protected array $schemaNames = [];
    protected array $tableNames = [];
    protected array $tableMetadata = [];

    /**
     * @var string|null the default schema name used for the current session.
     */
    protected ?string $defaultSchema = null;

    public function __construct(ConnectionInterface $connection, ?SchemaCache $schemaCache = null)
    {
        $this->connection = $connection;
        $this->schemaCache = $schemaCache;
    }

    /**
     * @inheritDoc
     */
    public function getRawTableName(string $name): string
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->connection->getTablePrefix(), $name);
        }

        return $name;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        return $this->getTableMetadata($name, 'schema', $refresh);
    }

    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'schema', $refresh);
    }

    public function getSchemaNames(bool $refresh = false): array
    {
        if (empty($this->schemaNames) || $refresh) {
            $this->schemaNames = $this->findSchemaNames();
        }

        return $this->schemaNames;
    }

    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return $this->tableNames[$schema];
    }

    public function refresh(): void
    {
        if ($this->schemaCache && $this->schemaCache->isEnabled()) {
            $this->schemaCache->invalidate($this->getCacheTag());
        }

        $this->tableNames = [];
        $this->tableMetadata = [];
    }

    public function refreshTableSchema(string $name): void
    {
        $rawName = $this->getRawTableName($name);

        unset($this->tableMetadata[$rawName]);

        $this->tableNames = [];

        if ($this->schemaCache && $this->schemaCache->isEnabled()) {
            $this->schemaCache->remove($this->getCacheKey($rawName));
        }
    }

    public function getDefaultSchema(): ?string
    {
        return $this->defaultSchema;
    }

    /**
     * Returns the metadata of the given type for the given table.
     *
     * If there's no metadata in the cache, this method will call a `'loadTable' . ucfirst($type)` named method with the
     * table name to obtain the metadata.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string $type metadata type.
     * @param bool $refresh whether to reload the table metadata even if it is found in the cache.
     *
     * @return mixed metadata.
     */
    protected function getTableMetadata(string $name, string $type, bool $refresh = false)
    {
        $rawName = $this->getRawTableName($name);

        if (!isset($this->tableMetadata[$rawName])) {
            $this->loadTableMetadataFromCache($rawName);
        }

        if ($refresh || !array_key_exists($type, $this->tableMetadata[$rawName])) {
            $this->tableMetadata[$rawName][$type] = $this->{'loadTable' . ucfirst($type)}($rawName);
            $this->saveTableMetadataToCache($rawName);
        }

        return $this->tableMetadata[$rawName][$type];
    }

    /**
     * Returns the metadata of the given type for all tables in the given schema.
     *
     * This method will call a `'getTable' . ucfirst($type)` named method with the table name and the refresh flag to
     * obtain the metadata.
     *
     * @param string $schema the schema of the metadata. Defaults to empty string, meaning the current or default schema
     * name.
     * @param string $type metadata type.
     * @param bool $refresh whether to fetch the latest available table metadata. If this is `false`, cached data may be
     * returned if available.
     *
     * @throws NotSupportedException
     *
     * @return array array of metadata.
     */
    protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array
    {
        $metadata = [];
        $methodName = 'getTable' . ucfirst($type);

        foreach ($this->getTableNames($schema, $refresh) as $name) {
            if ($schema !== '') {
                $name = $schema . '.' . $name;
            }

            $tableMetadata = $this->$methodName($name, $refresh);

            if ($tableMetadata !== null) {
                $metadata[] = $tableMetadata;
            }
        }

        return $metadata;
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [
            __CLASS__,
            $this->connection->getDsn(),
            $this->connection->getUsername(),
            $this->getRawTableName($name),
        ];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([
            __CLASS__,
            $this->connection->getDsn(),
            $this->connection->getUsername(),
        ]));
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     *
     * @return array all schema names in the database, except system schemas.
     */
    protected function findSchemaNames(): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all schema names.');
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     *
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all table names.');
    }

    /**
     * Tries to load and populate table metadata from cache.
     *
     * @param string $rawName
     */
    private function loadTableMetadataFromCache(string $rawName): void
    {
        if (empty($this->schemaCache) || !$this->schemaCache->isEnabled() || $this->schemaCache->isExcluded($rawName)) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        $metadata = $this->schemaCache->getOrSet(
            $this->getCacheKey($rawName),
            null,
            $this->schemaCache->getDuration(),
            new TagDependency($this->getCacheTag()),
        );

        if (
            !is_array($metadata) ||
            !isset($metadata['cacheVersion']) ||
            $metadata['cacheVersion'] !== static::SCHEMA_CACHE_VERSION
        ) {
            $this->tableMetadata[$rawName] = [];

            return;
        }

        unset($metadata['cacheVersion']);
        $this->tableMetadata[$rawName] = $metadata;
    }

    /**
     * Saves table metadata to cache.
     *
     * @param string $rawName
     */
    private function saveTableMetadataToCache(string $rawName): void
    {
        if (empty($this->schemaCache) || $this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
            return;
        }

        $metadata = $this->tableMetadata[$rawName];

        $metadata['cacheVersion'] = static::SCHEMA_CACHE_VERSION;

        $this->schemaCache->set(
            $this->getCacheKey($rawName),
            $metadata,
            $this->schemaCache->getDuration(),
            new TagDependency($this->getCacheTag()),
        );
    }
}
