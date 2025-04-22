<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;

/**
 * Use this value mapper for JSON structures.
 * If your field contains some JSON structure, DTO can unpack when retrieving the
 * value from the database and pack in into JSON again if you need to store it.
 */
#[Attribute]
final class JsonCodeValue implements FromPhpInterface, IntoPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        return json_encode($value);
    }

    public function intoPhpValue(mixed $value): mixed
    {
        return json_decode($value, true);
    }
}
