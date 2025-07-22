<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use PDOStatement;

/**
 * @template T
 */
class Statement
{
    private ?Primitive $primitiveType;

    /**
     * @template T
     * @param PDOStatement $stmt
     * @param class-string<T>  $targetType
     * @param MapperList $mapperList
     * @return Statement<T>
     */
    public function __construct(
        private PDOStatement $stmt,
        private string $targetType,
        private MapperList $mapperList,
    ) {
        $this->primitiveType = Primitive::create($targetType);
    }

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
    public function fetch(): mixed
    {
        if ($row = $this->stmt->fetch()) {
            if ($this->primitiveType) {
                return $this
                    ->primitiveType
                    ->castResult($row);
            } else {
                return $this
                    ->mapperList
                    ->getMapper($this->targetType)
                    ->fromAssoc($row);
            }
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

        if ($this->primitiveType) {
            $primitive = $this->primitiveType;
            foreach ($rows as $row) {
                $values[] = $primitive->castResult($row);
            }
        } else {
            $mapper = $this->mapperList->getMapper($this->targetType);
            foreach ($rows as $row) {
                $values[] = $mapper->fromAssoc($row);
            }
        }

        return $values;
    }
}
