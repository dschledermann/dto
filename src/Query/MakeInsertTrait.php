<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\DtoException;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeInsertTrait
{
    protected static function makeInsert(Mapper $mapper, SqlMode $sqlMode): string
    {
        $sq = $sqlMode->getStartQoute();
        $eq = $sqlMode->getEndQoute();
        $fields = $mapper->getFieldNames();
        $idField = $mapper->getUniqueField();

        if (!$idField) {
            throw new DtoException(sprintf(
                "[Aengeish3] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        foreach ($fields as $i => $field) {
            if ($field === $idField) {
                unset($fields[$i]);
                break;
            }
        }

        $fields = array_map(fn(string $a) => $sq.$a.$eq, $fields);

        return sprintf(
            'INSERT INTO %s%s%s (%s) VALUES (%s)',
            $sq,
            $mapper->getTableName(),
            $eq,
            implode(', ', $fields),
            implode(', ', array_fill(0, count($fields), '?')),
        );
    }
}
