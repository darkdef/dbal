<?php

declare(strict_types=1);

namespace Yiisoft\Dbal\Constraint;

/**
 * Constraint represents the metadata of a table constraint.
 */
interface ConstraintInterface
{
    public const PRIMARY_KEY = 'primaryKey';
    public const INDEXES = 'indexes';
    public const CHECKS = 'checks';
    public const FOREIGN_KEYS = 'foreignKeys';
    public const DEFAULT_VALUES = 'defaultValues';
    public const UNIQUES = 'uniques';

    public function getColumnNames();

    public function getName();

    /**
     * @param array|string|null $value list of column names the constraint belongs to.
     *
     * @return $this
     */
    public function columnNames($value): self;

    /**
     * @param object|string|null $value the constraint name.
     *
     * @return $this
     */
    public function name($value): self;
}
