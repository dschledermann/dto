<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\DtoException;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeDeleteTrait
{
    protected static function makeDelete(Mapper $mapper, SqlMode $sqlMode): string
    {
        $uniqueField = $mapper->getUniqueField();

        if (!$uniqueField) {
            throw new DtoException(sprintf(
                "[AP3xoieph] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        return sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $sqlMode->quoteName($mapper->getTableName()),
            $sqlMode->quoteName($mapper->getUniqueField()),
        );
    }
}
