<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value\FromPhp;

use Attribute;
use DateTimeImmutable;

#[Attribute]
final class FromDateTimeImmutable implements FromPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        /** @var DateTimeImmutable */
        return $value->format('Y-m-d H:i:s');
    }
}
