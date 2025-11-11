<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeBulkInsertTrait
{
    protected static function makeBulkInsertWithId(
        Mapper $mapper,
        SqlMode $sqlMode,
        int $chunkSize,
    ): string
    {
        return self::realMakeBulkInsert($mapper, $sqlMode, $chunkSize, false);
    }

    protected static function makeBulkInsertWithoutId(
        Mapper $mapper,
        SqlMode $sqlMode,
        int $chunkSize,
    ): string
    {
        return self::realMakeBulkInsert($mapper, $sqlMode, $chunkSize, true);
    }

    private static function realMakeBulkInsert(
        Mapper $mapper,
        SqlMode $sqlMode,
        int $chunkSize,
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

        $fields = array_map(fn(string $a) => $sqlMode->quoteName($a), $fields);

        $markers = implode(
            '), (',
            array_fill(
                0,
                $chunkSize,
                implode(
                    ', ',
                    array_fill(
                        0,
                        count($fields),
                        '?',
                    ),
                ),
            ),
        );
        
        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $sqlMode->quoteName($mapper->getTableName()),
            implode(', ', $fields),
            $markers,
        );
    }
}
