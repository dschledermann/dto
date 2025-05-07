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

        $fields = array_map(fn(string $a) => $sqlMode->qouteName($a), $fields);

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $sqlMode->qouteName($mapper->getTableName()),
            implode(', ', $fields),
            implode(', ', array_fill(0, count($fields), '?')),
        );
    }
}
