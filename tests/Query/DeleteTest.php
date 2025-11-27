<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Query\MakeDeleteTrait;
use Dschledermann\Dto\SqlMode;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    use MakeDeleteTrait;

    public function testHappyDelete(): void
    {
        $mapper = Mapper::create(DeleteRecord::class);

        $this->assertSame(
            "DELETE FROM `delete_record` WHERE `the_id` = ?",
            self::makeDelete($mapper, SqlMode::MySQL),
        );
    }

    public function testMissingId(): void
    {
        $this->expectExceptionMessage("[AP3xoieph]");
        $mapper = Mapper::create(DeleteWithoutId::class);
        self::makeDelete($mapper, SqlMode::MySQL);
    }
}

final class DeleteRecord
{
    #[UniqueIdentifier]
    public ?int $theId;
    public string $someField;
}

final class DeleteWithoutId
{
    public string $oneField;
    public string $anotherField;
}
