<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto;

use Dschledermann\Dto\MapperList;
use Dschledermann\Dto\Mapper\Ignore;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Statement;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    public function testArrayExecuteAndFetch(): void
    {
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with(['id' => 23])
            ->willReturn(true);

        $pdoStatement
            ->method('fetch')
            ->willReturn(['id' => 23, 'field1' => 'Hey', 'field2' => 'You!']);

        $statement = new Statement($pdoStatement, SomeDummy::class, new MapperList());
        $statement->execute(['id' => 23]);

        $obj = $statement->fetch();

        $this->assertInstanceOf(SomeDummy::class, $obj);
        $this->assertSame(23, $obj->id);
        $this->assertSame('Hey', $obj->field1);
    }

    public function testObjExecuteAndFetch(): void
    {
        $queryBag = new QueryBag('Hey', 'You', 'There');
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with(['some_field' => 'Hey', 'another_field' => 'You'])
            ->willReturn(true);

        $pdoStatement
            ->method('fetch')
            ->willReturn(['id' => 23, 'field1' => 'Hey', 'field2' => 'You!']);

        $statement = new Statement($pdoStatement, SomeDummy::class, new MapperList());
        $statement->execute($queryBag);

        $obj = $statement->fetch();

        $this->assertInstanceOf(SomeDummy::class, $obj);
        $this->assertSame(23, $obj->id);
        $this->assertSame('Hey', $obj->field1);

    }
}

final class SomeDummy
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $field1;
    public string $field2;
}

final class QueryBag
{
    public function __construct(
        public string $someField,
        public string $anotherField,
        #[Ignore]
        public string $aThirdField,
    ) {}
}
