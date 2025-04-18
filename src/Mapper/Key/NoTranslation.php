<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Key;

use Attribute;

#[Attribute]
final class NoTranslation implements KeyMapperInterface
{
    public function getFieldName(string $fieldName): string
    {
        return $fieldName;
    }
}
