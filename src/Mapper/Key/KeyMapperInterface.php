<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Key;

interface KeyMapperInterface
{
    public function getFieldName(string $fieldName): string;
}
