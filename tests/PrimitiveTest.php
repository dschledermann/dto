<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto;

use Dschledermann\Dto\Primitive;
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testCreatingKnowPrimitiveTypes(): void
    {
        $primitive = Primitive::create(Primitive::BOOLEAN);
        $this->assertEquals(Primitive::class, get_class($primitive));

        $primitive = Primitive::create(Primitive::FLOAT);
        $this->assertEquals(Primitive::class, get_class($primitive));

        $primitive = Primitive::create(Primitive::INTEGER);
        $this->assertEquals(Primitive::class, get_class($primitive));

        $primitive = Primitive::create(Primitive::STRING);
        $this->assertEquals(Primitive::class, get_class($primitive));
    }

    public function testCreatingUnknownPrimitiveType(): void
    {
        $primitive = Primitive::create(TestCase::class);
        $this->assertNull($primitive);
    }

    public function testConvertingValue(): void
    {
        $primitive = Primitive::create(Primitive::BOOLEAN);
        $this->assertSame(true, $primitive->castResult(["1"]));

        $primitive = Primitive::create(Primitive::FLOAT);
        $this->assertSame(3.14, $primitive->castResult(["3.14saas", 12]));

        $primitive = Primitive::create(Primitive::INTEGER);
        $this->assertSame(12, $primitive->castResult(["12dsfsdf", true, 1]));

        $primitive = Primitive::create(Primitive::STRING);
        $this->assertSame("12.2", $primitive->castResult([12.2]));
    }
}
