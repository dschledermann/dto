<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Mapper\Value;

use DateTimeImmutable;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\Value\CastDateTimeImmutable;
use Dschledermann\Dto\Mapper\Value\CastIp2Long;
use Dschledermann\Dto\Mapper\Value\FromToString;
use Dschledermann\Dto\Mapper\Value\IntoViaConstructorParam;
use Dschledermann\Dto\Mapper\Value\JsonCodeValue;
use PHPUnit\Framework\TestCase;
use Stringable;

final class ToAndFromTest extends TestCase
{
    public function testInto(): void
    {
        $values = [
            'date_field' => '2025-04-18 18:41:12',
            'int_field' => 123,
            'ip_address' => 3709862799,
            'list_of_strings' => '["7","9","13"]',
            'special_value' => 'test123',
        ];

        $mapper = Mapper::create(TypeWithDateAndIpField::class);

        $obj = $mapper->fromAssoc($values);

        $this->assertInstanceOf(TypeWithDateAndIpField::class, $obj);
        $this->assertSame(123, $obj->intField);
        $this->assertSame('221.32.3.143', $obj->ipAddress);
        $this->assertInstanceOf(DateTimeImmutable::class, $obj->dateField);
        $this->assertEquals(
            "2025-04-18 18:41:12",
            $obj->dateField->format('Y-m-d H:i:s'),
        );
        $this->assertEquals(['7', '9', '13'], $obj->listOfStrings);
        $this->assertInstanceOf(SpecialType::class, $obj->specialValue);
        $this->assertEquals("test123", $obj->specialValue->getValue());
    }

    public function testFrom(): void
    {
        $obj = new TypeWithDateAndIpField();
        $obj->dateField = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2025-04-19 15:00:18");
        $obj->ipAddress = '99.12.11.21';
        $obj->intField = 321;
        $obj->listOfStrings = ['abe', 'snot'];
        $obj->specialValue = new SpecialType("Make types great again");

        $mapper = Mapper::create(TypeWithDateAndIpField::class);
        $arr = $mapper->intoAssoc($obj);

        $this->assertIsArray($arr);
        $this->assertSame(321, $arr['int_field']);
        $this->assertSame(1661733653, $arr['ip_address']);
        $this->assertSame("2025-04-19 15:00:18", $arr['date_field']);
        $this->assertSame('["abe","snot"]', $arr['list_of_strings']);
        $this->assertSame("Make types great again", $arr['special_value']);
    }

    public function testNullablesFromValues(): void
    {
        $arr = ['other' => '123'];
        $mapper = Mapper::create(TypeWithNullableFields::class);
        $obj = $mapper->fromAssoc($arr);
        $this->assertNull($obj->ipAddress);
        $this->assertNull($obj->dateField);
        $this->assertSame(123, $obj->other);

        $arr = [
            'other' => 123,
            'ip_address' => '1577847707',
            'date_field' => '2026-04-13 12:18:23',
        ];
        $obj = $mapper->fromAssoc($arr);
        $this->assertEquals('94.12.11.155', $obj->ipAddress);
        $this->assertEquals((new DateTimeImmutable())->setTimestamp(1776082703), $obj->dateField);
    }

    public function testNullablesIntoValues(): void
    {
        $obj = new TypeWithNullableFields(null, null, 324324);
        $mapper = Mapper::create(TypeWithNullableFields::class);
        $assoc = $mapper->intoAssoc($obj);
        $this->assertNull($assoc['date_field']);
        $this->assertNull($assoc['ip_address']);
        $this->assertSame(324324, $assoc['other']);

        $obj = new TypeWithNullableFields(
            (new DateTimeImmutable())->setTimestamp(1776075773),
            '87.22.124.244',
            311676,
        );
        $assoc = $mapper->intoAssoc($obj);
        $this->assertSame('2026-04-13 10:22:53', $assoc['date_field']);
        $this->assertSame(1461091572, $assoc['ip_address']);
    }
}

final class TypeWithDateAndIpField
{
    #[CastDateTimeImmutable]
    public DateTimeImmutable $dateField;
    public int $intField;
    #[CastIp2Long]
    public string $ipAddress;
    #[JsonCodeValue]
    public array $listOfStrings;
    #[FromToString, IntoViaConstructorParam(SpecialType::class)]
    public SpecialType $specialValue;

}

final class TypeWithNullableFields
{
    public function __construct(
        #[CastDateTimeImmutable]
        public ?DateTimeImmutable $dateField = null,
        #[CastIp2Long]
        public ?string $ipAddress = null,
        public int $other,
    ) {}
}

final class SpecialType implements Stringable
{
    public function __construct(
        private string $param,
    ) {}

    public function getValue(): string
    {
        return $this->param;
    }

    public function __toString(): string
    {
        return $this->param;
    }
}
