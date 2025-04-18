<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Mapper\Value;

use DateTimeImmutable;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\Value\CastDateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ToAndFromTest extends TestCase
{
    public function testIntoDatetimeImmutable(): void
    {
        $values = [
            'date_field' => '2025-04-18 18:41:12',
            'int_field' => 123,
            'string_field' => 'yui7Ahr3s',
        ];

        $mapper = Mapper::create(TypeWithDateField::class);

        $obj = $mapper->fromAssoc($values);

        $this->assertEquals(123, $obj->intField);
        $this->assertEquals('yui7Ahr3s', $obj->stringField);
        $this->assertInstanceOf(DateTimeImmutable::class, $obj->dateField);
        $this->assertEquals(
            "2025-04-18 18:41:12",
            $obj->dateField->format('Y-m-d H:i:s'),
        );
    }
}

final class TypeWithDateField
{
    #[CastDateTimeImmutable]
    public DateTimeImmutable $dateField;
    public int $intField;
    public string $stringField;
}
