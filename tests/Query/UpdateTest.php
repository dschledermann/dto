<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Query;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\Ignore;
use Dschledermann\Dto\Mapper\Key\SetSqlName;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Query\MakeUpdateTrait;
use Dschledermann\Dto\SqlMode;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    use MakeUpdateTrait;

    public function testHappyUpdate(): void
    {
        $mapper = Mapper::create(UpdateRecord::class);

        $this->assertSame(
            'UPDATE `update_record` SET `field1` = ?, `extra_field` = ?, `rename_field` = ? WHERE `id` = ?',
            static::makeUpdate($mapper, SqlMode::MySQL),
        );

        $this->assertSame(
            'UPDATE "update_record" SET "field1" = ?, "extra_field" = ?, "rename_field" = ? WHERE "id" = ?',
            static::makeUpdate($mapper, SqlMode::ANSI),
        );
    }

    public function testMissingId(): void
    {
        $mapper = Mapper::create(UpdateWithoutId::class);
        $this->expectExceptionMessage("[eu7Phahm4]");
        static::makeUpdate($mapper, SqlMode::MySQL);
    }
}

final class UpdateRecord
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $field1;
    public string $extraField;
    #[SetSqlName("rename_field")]
    public string $otherField;
    #[Ignore]
    public string $ignoredField;
}

final class UpdateWithoutId
{
    public string $field1;
    public string $field2;
}
