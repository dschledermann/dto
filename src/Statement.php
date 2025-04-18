<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use PDOStatement;

/**
 * @template T
 */
final class Statement
{
    /**
     * @template T
     * @param PDOStatement $stmt
     * @param Mapper<T> $mapper
     * @return Statement<T>
     */
    public function __construct(
        private PDOStatement $stmt,
        private Mapper $mapper,
    ) {}

    public function execute(array $params): void
    {
        $this->stmt->execute($params);
    }

    /**
     * @return T|null
     */
    public function fetch(): ?object
    {
        if ($row = $this->stmt->fetch()) {
            return $this->mapper->makeFromAssoc($row);
        } else {
            return null;
        }
    }

    /**
     * @return array<T>
     */
    public function fetchAll(): array
    {
        $rows = $this->stmt->fetchAll();
        $values = [];

        foreach ($rows as $row) {
            $values[] = $this->mapper->makeFromAssoc($row);
        }

        return $values;
    }
}
