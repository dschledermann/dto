<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\DtoException;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeCountByIdColumnTrait
{
    protected static function makeCountByIdColumn(Mapper $mapper, SqlMode $sqlMode): string
    {
        $idField = $mapper->getUniqueField();

        if (!$idField) {
            throw new DtoException(sprintf(
                "[hei3woo3E] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        return sprintf(
            "SELECT COUNT(*) AS val FROM %s WHERE %s = ?",
            $sqlMode->quoteName($mapper->getTableName()),
            $sqlMode->quoteName($idField),
        );
    }
}
