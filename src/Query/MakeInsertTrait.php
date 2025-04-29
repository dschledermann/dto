<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeInsertTrait
{
    protected static function makeInsertWithId(Mapper $mapper, SqlMode $sqlMode): string
    {
        return self::realMakeInsert($mapper, $sqlMode, false);
    }

    protected static function makeInsertWithoutId(Mapper $mapper, SqlMode $sqlMode): string
    {
        return self::realMakeInsert($mapper, $sqlMode, true);
    }

    private static function realMakeInsert(
        Mapper $mapper,
        SqlMode $sqlMode,
        bool $dropIdField,
    ): string
    {
        $sq = $sqlMode->getStartQoute();
        $eq = $sqlMode->getEndQoute();
        $fields = $mapper->getFieldNames();

        if ($dropIdField) {
            $idField = $mapper->getUniqueField();
            foreach ($fields as $i => $field) {
                if ($field === $idField) {
                    unset($fields[$i]);
                    break;
                }
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
