<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\DtoException;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeSelectOneTrait
{
    protected static function makeSelectOne(Mapper $mapper, SqlMode $sqlMode): string
    {
        $sq = $sqlMode->getStartQoute();
        $eq = $sqlMode->getEndQoute();
        $uniqueField = $mapper->getUniqueField();

        if (!$uniqueField) {
            throw new DtoException(sprintf(
                "[oonaiRe4e] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        return sprintf(
            'SELECT * FROM %s%s%s WHERE %s%s%s = ?',
            $sq,
            $mapper->getTableName(),
            $eq,
            $sq,
            $mapper->getUniqueField(),
            $eq,
        );
    }
}
