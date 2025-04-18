<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Key;

use Attribute;

#[Attribute]
final class ToLowerCase implements KeyMapperInterface
{
    public function getFieldName(string $fieldName): string
    {
        return strtolower($fieldName);
    }
}
