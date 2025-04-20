<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;

#[Attribute]
final class CastIp2Long implements FromPhpInterface, IntoPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        return ip2long($value);
    }

    public function intoPhpValue(mixed $value): mixed
    {
        return long2ip((int)$value);
    }
}
