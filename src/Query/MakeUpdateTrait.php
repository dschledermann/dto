<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Query;

use Dschledermann\Dto\DtoException;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\SqlMode;

trait MakeUpdateTrait
{
    protected static function makeUpdate(Mapper $mapper, SqlMode $sqlMode): string
    {
        $fields = $mapper->getFieldNames();
        $idField = $mapper->getUniqueField();

        if (!$idField) {
            throw new DtoException(sprintf(
                "[eu7Phahm4] No unique id field for %s. Cannot construct SQL.",
                $mapper->getTableName(),
            ));
        }

        $result = [];

        foreach ($fields as $field) {
            if ($field <> $idField) {
                $result[] = $sqlMode->quoteName($field) . ' = ?';
            }
        }

        return sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $sqlMode->quoteName($mapper->getTableName()),
            implode(', ', $result),
            $sqlMode->quoteName($mapper->getUniqueField()),
        );
    }
}
