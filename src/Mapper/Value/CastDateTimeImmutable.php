<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;
use DateTimeImmutable;

#[Attribute]
final class CastDateTimeImmutable implements FromPhpInterface, IntoPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        /** @var DateTimeImmutable */
        return $value->format('Y-m-d H:i:s');
    }

    public function intoPhpValue($mixed): mixed
    {
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $mixed);
    }
}
