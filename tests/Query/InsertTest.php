<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Query\MakeInsertTrait;
use Dschledermann\Dto\SqlMode;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
{
    use MakeInsertTrait;

    public function testHappyInsert(): void
    {
        $mapper = Mapper::create(InsertRecord::class);
        $this->assertEquals(
            'INSERT INTO `insert_record` (`id`, `field1`, `field2`) VALUES (?, ?, ?)',
            self::makeInsertWithId($mapper, SqlMode::MySQL),
        );

        $this->assertEquals(
            'INSERT INTO `insert_record` (`field1`, `field2`) VALUES (?, ?)',            
            self::makeInsertWithoutId($mapper, SqlMode::MySQL),
        );
    }
}

final class InsertRecord
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $field1;
    public string $field2;
}
