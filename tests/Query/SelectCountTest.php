<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\SqlMode;
use Dschledermann\Dto\Query\MakeCountByIdColumnTrait;
use PHPUnit\Framework\TestCase;

class SelectCountTest extends TestCase
{
    use MakeCountByIdColumnTrait;

    public function testSelectCountHappy(): void
    {
        $mapper = Mapper::create(CountRecord::class);
        $this->assertSame(
            'SELECT COUNT(*) AS num_records FROM `count_record` WHERE `id` = ?',
            static::makeCountByIdColumn($mapper, SqlMode::MySQL),
        );
    }

    public function testMissingId(): void
    {
        $this->expectExceptionMessage("[hei3woo3E]");
        $mapper = Mapper::create(CountWithoutId::class);
        static::makeCountByIdColumn($mapper, SqlMode::MySQL);
    }
}

final class CountRecord
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $someField;
}

final class CountWithoutId
{
    public string $someField;
}
