<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;

/**
 * This value mapper will pack an IP-address in an efficient int in the database
 * and unpack it into a string in PHP.
 */
#[Attribute]
final class CastIp2Long implements FromPhpInterface, IntoPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        if (!is_null($value)) {
            return ip2long($value);
        } else {
            return null;
        }
    }

    public function intoPhpValue(mixed $value): mixed
    {
        if (!is_null($value)) {
            return long2ip((int)$value);
        } else {
            return null;
        }
    }
}
