<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Key;

use Attribute;

#[Attribute]
final class ToSnakeCase implements KeyMapperInterface
{
    public function getFieldName(string $fieldName): string
    {
        return ltrim(strtolower(preg_replace('/([A-Z])/', '_\\1', $fieldName)), '_');
    }
}
