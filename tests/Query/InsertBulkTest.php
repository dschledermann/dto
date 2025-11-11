<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Query\MakeBulkInsertTrait;
use Dschledermann\Dto\SqlMode;
use PHPUnit\Framework\TestCase;

class InsertBulkTest extends TestCase    
{
    use MakeBulkInsertTrait;

    public function testHappyBulkInsert(): void
    {
        $mapper = Mapper::create(ToBeBulkInserted::class);
        $this->assertEquals(
            "INSERT INTO `to_be_bulk_inserted` (`id`, `field1`, `field2`) VALUES (?, ?, ?), (?, ?, ?)",
            self::makeBulkInsertWithId($mapper, SqlMode::MySQL, 2),
        );

        $this->assertEquals(
            "INSERT INTO `to_be_bulk_inserted` (`field1`, `field2`) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            self::makeBulkInsertWithoutId($mapper, SqlMode::MySQL, 4),
        );
    }
}

final class ToBeBulkInserted
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $field1;
    public string $field2;
}
