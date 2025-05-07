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
        $uniqueField = $mapper->getUniqueField();

        if (!$uniqueField) {
            throw new DtoException(sprintf(
                "[oonaiRe4e] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        return sprintf(
            'SELECT * FROM %s WHERE %s = ?',
            $sqlMode->qouteName($mapper->getTableName()),
            $sqlMode->qouteName($mapper->getUniqueField()),
        );
    }
}
