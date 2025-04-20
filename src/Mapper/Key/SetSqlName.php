<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Key;

use Attribute;

#[Attribute]
final class SetSqlName implements KeyMapperInterface
{
    public function __construct(
        private string $forcedName,
    ) {}

    public function getFieldName(string $fieldName): string
    {
        return $this->forcedName;
    }
}
