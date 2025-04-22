<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;

/**
 * This value mapper will take any Stringable and call the __toString() method
 * for storage in the database.
 */
#[Attribute]
final class FromToString implements FromPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        return $value->__toString();
    }
}
