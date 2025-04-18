<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use Dschledermann\Dto\Query\MakeInsertTrait;
use Dschledermann\Dto\Query\MakeSelectOneTrait;
use Dschledermann\Dto\Query\MakeUpdateTrait;
use PDO;

final class Connection
{
    use GetMapperTrait;
    use MakeInsertTrait;
    use MakeSelectOneTrait;
    use MakeUpdateTrait;

    private function __construct(
        private PDO $pdo,
        private SqlMode $sqlMode,
    ) {}

    public static function createFromPdo(
        PDO $pdo,
        SqlMode $sqlMode = SqlMode::MySQL,
    ): Connection
    {
        return new Connection($pdo);
    }

    public static function createFromEnv(
        array $pdoParams = [],
        string $varName = 'DATABASE_URL',
    ): Connection
    {
        $url = getenv($varName);
        $dsn = sprintf(
            "%s:host=%s;dbname=%s",
            $url["scheme"],
            $url["host"],
            ltrim($url["path"], "/")
        );
        $username = $url['user'];
        $password = $url['pass'];

        $pdo = new PDO($dsn, $username, $password, $pdoParams);

        if ($url['scheme'] == 'mysql') {
            $sqlMode = SqlMode::MySQL;
        } else {
            $sqlMode = SqlMode::ANSI;
        }

        return new Connection($pdo, $sqlMode);
    }

    /**
     * @template T
     * @param string $sql
     * @param class-string<T> $targetClass
     * @return Statement<T>
     */
    public function query(string $sql, string $targetClass): Statement
    {
        $stmt = $this->prepare($sql, $targetClass);
        $stmt->execute([]);
        return $stmt;
    }

    /**
     * @template T
     * @param string $sql
     * @param class-string<T> $targetClass
     * @return Statement<T>
     */
    public function prepare(string $sql, string $targetClass): Statement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return new Statement($stmt, self::getMapper($targetClass));
    }

    /**
     * @template T
     * @param mixed             $id             Unique id for record
     * @param class-string<T>   $targetclass    DTO class
     * @return Statement<T>
     */
    public function get(mixed $id, string $targetClass): Statement
    {
        $mapper = self::getMapper($targetClass);
        $sql = self::makeSelectOne($mapper, $this->sqlMode);
        $stmt = $this->prepare($sql, $targetClass);
        $stmt->execute([$id]);
        return $stmt;
    }

    /**
     * @template T
     * @param    T             $obj
     * @return   Statement<T>
     */
    public function persist(object $obj)
    {
        $className = get_class($obj);
        $mapper = self::getMapper($className);

        if (is_null($mapper->getUniqueField())) {
            throw new DtoException(sprintf(
                "[oor4enaoR] %s does not have a unique field. Cannot use persist()",
                $mapper->getTableName(),
            ));
        }

        $idProperty = $mapper->getUniqueProperty();
        $idField = $mapper->getUniqueField();

        $id = $obj->$idProperty;

        $fields = $mapper->makeAssocFromObject($obj);

        if ($id) {
            $sql = self::makeUpdate($mapper, $this->sqlMode);
            unset($fields[$idField]);
            $fields[$idField] = $id;
        } else {
            $sql = self::makeInsert($mapper, $this->sqlMode);
        }

        $stmt = $this->prepare($sql);
        $stmt->execute(array_values($fields));
        return $stmt;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function errorCode(): ?string
    {
        return $this->pdo->errorCode();
    }

    public function errorInfo(): array
    {
        return $this->pdo->errorInfo();
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
