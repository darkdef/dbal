<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Schema;

use Throwable;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Dbal\Cache\SchemaCache;
use Yiisoft\Dbal\Connection\ConnectionInterface;

class AbstractSchema implements SchemaInterface
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
     * Tries to load and populate table metadata from cache.
     *
     * @param string $rawName
     */
    private function loadTableMetadataFromCache(string $rawName): void
    {
        if (empty($this->schemaCache)) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        if (!$this->schemaCache->isEnabled() || $this->schemaCache->isExcluded($rawName)) {
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
        if (empty($this->schemaCache)) {
            return;
        }

        if ($this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
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

    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        // TODO: Implement getTableSchemas() method.
    }

    public function getSchemaNames(bool $refresh = false): array
    {
        // TODO: Implement getSchemaNames() method.
    }

    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        // TODO: Implement getTableNames() method.
    }

    public function refresh(): void
    {
        // TODO: Implement refresh() method.
    }

    public function refreshTableSchema(string $name): void
    {
        // TODO: Implement refreshTableSchema() method.
    }

    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        // TODO: Implement findUniqueIndexes() method.
    }

    public function getDefaultSchema(): ?string
    {
        // TODO: Implement getDefaultSchema() method.
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
}
