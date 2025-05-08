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

    public function testGettingUniqueFieldInDtoWithoutUniqueField(): void
    {
        $this->expectExceptionMessage('[ohya9tu4X]');
        $dto = new SimpleDto('some-string', 12.44);
        $mapper = Mapper::create(SimpleDto::class);
        $mapper->getUniqueValue($dto);
    }

    public function testSettingUniqueFieldInDtoWithoutUniqueField(): void
    {
        $this->expectExceptionMessage('[eNigah4bi]');
        $dto = new SimpleDto('some-string', 12.44);
        $mapper = Mapper::create(SimpleDto::class);
        $mapper->setUniqueValue($dto, "7c1ef27c-2bd8-11f0-aad0-f76d6847cabb");
    }

    public function testGettingAndSettingUniqueField(): void
    {
        $dto = new TestDto(null, 'John', 'ohVae9ooM', 12.077, 123);
        $mapper = Mapper::create(TestDto::class);

        $this->assertNull($mapper->getUniqueValue($dto));

        $mapper->setUniqueValue($dto, "123");
        $this->assertEquals(123, $dto->id);

        $mapper->setUniqueValue($dto, 666);
        $this->assertEquals(666, $dto->id);
    }
}

class SimpleDto
{
    public function __construct(
        public string $field,
        public float $anotherField,
    ) {}
}

#[SetSqlName("another_table_name")]
class TestDto
{
    public function __construct(
        #[UniqueIdentifier]
        public ?int $id,
        public string $name,
        public string $someField,
        #[SetSqlName("new_name")]
        public float $renamedField,
        #[Ignore]
        public int $ignoredField,
    ) {}
}
