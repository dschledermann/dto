<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Mapper\Value;

use DateTimeImmutable;
use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\Value\CastDateTimeImmutable;
use Dschledermann\Dto\Mapper\Value\CastIp2Long;
use PHPUnit\Framework\TestCase;

final class ToAndFromTest extends TestCase
{
    public function testInto(): void
    {
        $values = [
            'date_field' => '2025-04-18 18:41:12',
            'int_field' => 123,
            'ip_address' => 3709862799,
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
    }

    public function testFrom(): void
    {
        $obj = new TypeWithDateAndIpField();
        $obj->dateField = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2025-04-19 15:00:18");
        $obj->ipAddress = '99.12.11.21';
        $obj->intField = 321;

        $mapper = Mapper::create(TypeWithDateAndIpField::class);
        $arr = $mapper->intoAssoc($obj);

        $this->assertIsArray($arr);
        $this->assertSame(321, $arr['int_field']);
        $this->assertSame(1661733653, $arr['ip_address']);
        $this->assertSame("2025-04-19 15:00:18", $arr['date_field']);
    }
}

final class TypeWithDateAndIpField
{
    #[CastDateTimeImmutable]
    public DateTimeImmutable $dateField;
    public int $intField;
    #[CastIp2Long]
    public string $ipAddress;
}
