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
        $sq = $sqlMode->getStartQoute();
        $eq = $sqlMode->getEndQoute();
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
                $result[] = sprintf('%s%s%s = ?', $sq, $field, $eq);
            }
        }

        return sprintf(
            'UPDATE %s%s%s SET %s WHERE %s%s%s = ?',
            $sq,
            $mapper->getTableName(),
            $eq,
            implode(', ', $result),
            $sq,
            $mapper->getUniqueField(),
            $eq,
        );
    }
}
