<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;
use DateTimeImmutable;

/**
 * You can use this value mapper if your field is a datatime
 * It will become a datetimeimmutable in PHP
 */
#[Attribute]
final class CastDateTimeImmutable implements FromPhpInterface, IntoPhpInterface
{
    public function fromPhpValue(mixed $value): mixed
    {
        if (is_a($value, DateTimeImmutable::class)) {
            return $value->format('Y-m-d H:i:s');
        } else {
            return null;
        }
    }

    public function intoPhpValue($value): mixed
    {
        if (!is_null($value)) {
            return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        } else {
            return null;
        }
    }
}
