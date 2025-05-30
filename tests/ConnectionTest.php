<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto;

use Dschledermann\Dto\Connection;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testCreateFromPdo(): void
    {
        $pdo = $this->createMock(PDO::class);
        $connection = Connection::createFromPdo($pdo);
        $this->assertSame($pdo, $connection->getPdo());
    }

    public function testRunPrepareAndExecute(): void
    {
        $sql = "SELECT * FROM some_simple_type WHERE id = ?";
        $params = [666];

        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with($params)
            ->willReturn(true);

        $pdoStatement
            ->method('fetch')
            ->willReturn(
                ['id' => 666, 'field1' => 'Hej, hej, Dr. Pjuskebusk', 'field2' => 'a'],
                false,
            );

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method('prepare')
            ->with($sql)
            ->willReturn($pdoStatement);

        $connection = Connection::createFromPdo($pdo);

        $stmt = $connection->prepare($sql, SomeSimpleType::class);
        $stmt->execute($params);
        $obj = $stmt->fetch();

        $this->assertInstanceOf(SomeSimpleType::class, $obj);
        $this->assertSame(666, $obj->id);
        $this->assertSame("Hej, hej, Dr. Pjuskebusk", $obj->field1);

        $this->assertNull($stmt->fetch());
    }

    public function testRunQuery(): void
    {
        $sql = "SELECT * FROM some_simple_type";
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method("execute")
            ->with([])
            ->willReturn(true);

        $pdoStatement
            ->method("fetchAll")
            ->willReturn([
                ['id' => 1, 'field1' => 'Foo', 'field2' => 'a'],
                ['id' => 2, 'field1' => 'Bar', 'field2' => 'b'],
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->with($sql)
            ->willReturn($pdoStatement);

        $connection = Connection::createFromPdo($pdo);
        $stmt = $connection->query($sql, SomeSimpleType::class);

        $objs = $stmt->fetchAll();

        $this->assertIsArray($objs);
        $this->assertSame(2, count($objs));
        $this->assertInstanceOf(SomeSimpleType::class, $objs[0]);
        $this->assertInstanceOf(SomeSimpleType::class, $objs[1]);
    }

    public function testRunGet(): void
    {
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method("execute")
            ->with([123])
            ->willReturn(true);

        $pdoStatement
            ->method("fetch")
            ->willReturn(
                ['id' => 123, 'field1' => "Hej, hej, Martin og Ketil", 'field2' => 'a'],
                null,
            );

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method('prepare')
            ->with("SELECT * FROM `some_simple_type` WHERE `id` = ?")
            ->willReturn($pdoStatement);

        $connection = Connection::createFromPdo($pdo);
        $stmt = $connection->get(123, SomeSimpleType::class);
        $obj = $stmt->fetch();

        $this->assertInstanceOf(SomeSimpleType::class, $obj);
        $this->assertSame(123, $obj->id);
        $this->assertSame("Hej, hej, Martin og Ketil", $obj->field1);
    }

    public function testPersistWithId(): void
    {
        $pdoStatementCheckExists = $this->createMock(PDOStatement::class);
        $pdoStatementCheckExists
            ->method('execute')
            ->with([12])
            ->willReturn(true);

        $pdoStatementCheckExists
            ->method('fetch')
            ->willReturn(['num_records' => 1]);

        $pdoStatementUpdate = $this->createMock(PDOStatement::class);
        $pdoStatementUpdate
            ->method('execute')
            ->with(['Davs du', 'q', 12])
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->withAnyParameters()
            ->willReturn($pdoStatementCheckExists, $pdoStatementUpdate);

        $obj = new SomeSimpleType(12, 'Davs du', 'q');

        $connection = Connection::createFromPdo($pdo);
        $stmt = $connection->persist($obj);
        $this->assertTrue(true);
    }

    public function testPersistWithIdButWithoutRecord(): void
    {
        $pdoStatementCheckExists = $this->createMock(PDOStatement::class);
        $pdoStatementCheckExists
            ->method('execute')
            ->with([12])
            ->willReturn(true);

        $pdoStatementCheckExists
            ->method('fetch')
            ->willReturn(['num_records' => 0]);

        $pdoStatementInsert = $this->createMock(PDOStatement::class);
        $pdoStatementInsert
            ->method('execute')
            ->with([12, 'Davs du', 'q'])
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->withAnyParameters()
            ->willReturn($pdoStatementCheckExists, $pdoStatementInsert);

        $obj = new SomeSimpleType(12, 'Davs du', 'q');

        $connection = Connection::createFromPdo($pdo);
        $stmt = $connection->persist($obj);
        $this->assertTrue(true);

    }

    public function testPersistWithoutId(): void
    {
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with(['Davs du', 'q'])
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->with("INSERT INTO `some_simple_type` (`field1`, `field2`) VALUES (?, ?)")
            ->willReturn($pdoStatement);
        $pdo
            ->method("lastInsertId")
            ->willReturn("666");

        $obj = new SomeSimpleType(null, 'Davs du', 'q');

        $connection = Connection::createFromPdo($pdo);
        $stmt = $connection->persist($obj);

        $this->assertSame(666, $obj->id);
    }

    public function testTryingToPersistTypeWithoutId(): void
    {
        $this->expectExceptionMessage("[oor4enaoR]");
        $pdo = $this->createMock(PDO::class);
        $connection = Connection::createFromPdo($pdo);
        $obj = new TypeWithOutId("Meh", "Meh", "Meh", 4);
        $connection->persist($obj);
    }

    public function testInsertWithoutIdColumn(): void
    {
        $obj = new TypeWithOutId("Make your own", "Music", "clown", 1234);

        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method("execute")
            ->with(["Make your own", "Music", 'clown', 1234]);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->with("INSERT INTO `type_with_out_id` (`field_number1`, `field_number2`, `meme`, `fourth`) VALUES (?, ?, ?, ?)")
            ->willReturn($pdoStatement);

        $connection = Connection::createFromPdo($pdo);
        $connection->insert($obj);
        $this->assertTrue(true);
    }

    public function testInsertWithIdColumnNull(): void
    {
        $obj = new SomeSimpleType(null, "Most amazing", "Nobody's ever seen anything like it");

        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with(["Most amazing", "Nobody's ever seen anything like it"]);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->with('INSERT INTO `some_simple_type` (`field1`, `field2`) VALUES (?, ?)')
            ->willReturn($pdoStatement);
        $pdo
            ->method('lastInsertId')
            ->willReturn('123123');

        $connection = Connection::createFromPdo($pdo);
        $connection->insert($obj);

        $this->assertSame(123123, $obj->id);
    }

    public function testHealthyUpdate(): void
    {
        $obj = new SomeSimpleType(5, 'Laws', 'Of stupidity');

        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement
            ->method('execute')
            ->with(['Laws', 'Of stupidity', 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo
            ->method("prepare")
            ->with('UPDATE `some_simple_type` SET `field1` = ?, `field2` = ? WHERE `id` = ?')
            ->willReturn($pdoStatement);

        $connection = Connection::createFromPdo($pdo);
        $connection->update($obj);
        $this->assertTrue(true);
    }

    public function testUpdateWithoutIdField(): void
    {
        $this->expectExceptionMessage('[Eg7Ahdoh9]');
        $obj = new TypeWithOutId('Do', 'Ray', 'Egon', 1989);
        $pdo = $this->createMock(PDO::class);
        $connection = Connection::createFromPdo($pdo);
        $connection->update($obj);
    }

    public function testUpdateWithNullId(): void
    {
        $this->expectExceptionMessage('[eiK9Aegai]');
        $obj = new SomeSimpleType(null, 'Bricks', 'To Make');
        $pdo = $this->createMock(PDO::class);
        $connection = Connection::createFromPdo($pdo);
        $connection->update($obj);
    }

    public function testProxies(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo
            ->expects($this->once())
            ->method('beginTransaction');
        $pdo
            ->expects($this->once())
            ->method('commit');
        $pdo
            ->expects($this->once())
            ->method('rollback');
        $pdo
            ->expects($this->once())
            ->method('errorCode')
            ->willReturn("Damn!");
        $pdo
            ->expects($this->once())
            ->method('errorInfo')
            ->willReturn([1, 2, 3]);
        $pdo
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1975');

        $connection = Connection::createFromPdo($pdo);
        $connection->beginTransaction();
        $connection->commit();
        $connection->rollback();
        $this->assertSame('Damn!', $connection->errorCode());
        $this->assertSame([1, 2, 3], $connection->errorInfo());
        $this->assertSame('1975', $connection->lastInsertId());
    }
}

final class SomeSimpleType
{
    public function __construct(
        #[UniqueIdentifier]
        public ?int $id,
        public string $field1,
        public string $field2,
    ) {}
}

final class TypeWithOutId
{
    public function __construct(
        public string $fieldNumber1,
        public string $fieldNumber2,
        public string $meme,
        public int $fourth,
    ) {}
}
