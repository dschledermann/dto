<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto;

use Dschledermann\Dto\Mapper;
use Dschledermann\Dto\Mapper\Ignore;
use Dschledermann\Dto\Mapper\Key\SetSqlName;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testSimpleMapping(): void
    {
        $mapper = Mapper::create(SimpleDto::class);
        $this->assertNull($mapper->getUniqueField());
        $this->assertSame('simple_dto', $mapper->getTableName());
    }

    public function testSlightlyMoreAdvancedDto(): void
    {
        $mapper = Mapper::create(TestDto::class);
        $this->assertSame('id', $mapper->getUniqueField());
        $this->assertSame('another_table_name', $mapper->getTableName());
    }

    public function testCreatingDtoFromAssocArray(): void
    {
        $mapper = Mapper::create(SimpleDto::class);

        $row = [
            'field' => 'Hej, hej, Dr. Pjuskebusk',
            'another_field' => 2.71828,
            'unrelated_field' => 'Hej, hej, Martin og Ketil',
        ];

        $dto = $mapper->fromAssoc($row);

        $this->assertSame(get_class($dto), SimpleDto::class);
        $this->assertSame('Hej, hej, Dr. Pjuskebusk', $dto->field);
        $this->assertSame(2.71828, $dto->anotherField);
    }

    public function testMissingFieldFromAssoc(): void
    {
        $this->expectExceptionMessage('[eiiaNg9ph]');
        $mapper = Mapper::create(SimpleDto::class);
        $arr = ['field' => 'mememem'];
        $mapper->fromAssoc($arr);
    }
}

class SimpleDto
{
    public string $field;
    public float $anotherField;
}

#[SetSqlName("another_table_name")]
class TestDto
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $name;
    public string $someField;
    #[SetSqlName("new_name")]
    public float $renamedField;
    #[Ignore]
    public int $ignoredField;
}
