<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Query\MakeSelectOneTrait;
use Dschledermann\Dto\SqlMode;
use PHPUnit\Framework\TestCase;

class SelectOneTest extends TestCase
{
    use MakeSelectOneTrait;

    public function testSelectOneHappy(): void
    {
        $mapper = Mapper::create(SelectRecord::class);
        $this->assertSame(
            'SELECT * FROM `select_record` WHERE `id` = ?',
            static::makeSelectOne($mapper, SqlMode::MySQL),
        );

        $this->assertSame(
            'SELECT * FROM "select_record" WHERE "id" = ?',
            static::makeSelectOne($mapper, SqlMode::ANSI),
        );
    }

    public function testMissingId(): void
    {
        $mapper = Mapper::create(SelectWithoutId::class);
        $this->expectExceptionMessage("[oonaiRe4e]");
        static::makeSelectOne($mapper, SqlMode::MySQL);
    }
}

final class SelectRecord
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $name;
    public int $postalCode;
}

final class SelectWithoutId
{
    public string $field1;
    public string $field2;
    public float $field3;
}
