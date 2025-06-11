<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use PDOStatement;

/**
 * @template T
 */
class Statement
{
    /**
     * @template T
     * @param PDOStatement $stmt
     * @param class-string<T>  $targetClass
     * @param MapperList $mapperList
     * @return Statement<T>
     */
    public function __construct(
        private PDOStatement $stmt,
        private string $targetClass,
        private MapperList $mapperList,
    ) {}

    /**
     * @param array|object $params  Parameters for the query execution.
     * @return bool
     */
    public function execute(array|object $params): bool
    {
        if (is_object($params)) {
            $mapper = $this->mapperList->getMapper(get_class($params));
            $params = $mapper->intoAssoc($params);
        }
        return $this->stmt->execute($params);
    }

    /**
     * @return T|null
     */
    public function fetch(): ?object
    {
        if ($row = $this->stmt->fetch()) {
            return $this
                ->mapperList
                ->getMapper($this->targetClass)
                ->fromAssoc($row);
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
        $mapper = $this->mapperList->getMapper($this->targetClass);

        foreach ($rows as $row) {
            $values[] = $mapper->fromAssoc($row);
        }

        return $values;
    }
}
