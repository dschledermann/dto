<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value\IntoPhp;

use Attribute;
use DateTimeImmutable;

#[Attribute]
final class IntoDatetimeImmutable implements IntoPhpInterface
{
    public function intoPhpValue($mixed): mixed
    {
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $mixed);
    }
}
