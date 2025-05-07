<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use Dschledermann\Dto\DefaultTypes\NumRecords;
use Dschledermann\Dto\Query\MakeCountByIdColumnTrait;
use Dschledermann\Dto\Query\MakeInsertTrait;
use Dschledermann\Dto\Query\MakeSelectOneTrait;
use Dschledermann\Dto\Query\MakeUpdateTrait;
use PDO;

final class Connection
{
    use MakeCountByIdColumnTrait;
    use MakeInsertTrait;
    use MakeSelectOneTrait;
    use MakeUpdateTrait;

    /** @var array<string, Statement> */
    private array $prepareStore = [];

    private function __construct(
        private PDO $pdo,
        private SqlMode $sqlMode,
        private MapperList $mapperList,
    ) {}

    public static function createFromPdo(
        PDO $pdo,
        SqlMode $sqlMode = SqlMode::MySQL,
    ): Connection
    {
        return new Connection($pdo, $sqlMode, new MapperList());
    }

    /**
     * @param array $pdoParams Params passed to the PDO creation.
     * @param string $varName Name of environment variable holding the URL.
     * @return Connection
     */
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

        return new Connection($pdo, $sqlMode, new MapperList());
    }

    /**
     * Run a query and map the result onto the provided class.
     *
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
     * Prepare a query and map the result onto the provided class.
     *
     * @template T
     * @param string $sql
     * @param class-string<T> $targetClass
     * @return Statement<T>
     */
    public function prepare(string $sql, string $targetClass): Statement
    {
        $key = md5($targetClass . $sql);

        if (!array_key_exists($key, $this->prepareStore)) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $this->prepareStore[$key] = new Statement(
                $stmt,
                $targetClass,
                $this->mapperList,
            );
        }

        return $this->prepareStore[$key];
    }

    /**
     * Run a trivial SELECT query getting a DTO by its unique identifier.
     *
     * @template T
     * @param mixed $id             Unique id for record
     * @param class-string<T> $targetClass    Mapped onto this class
     * @return Statement<T>
     */
    public function get(mixed $id, string $targetClass): Statement
    {
        $mapper = $this->mapperList->getMapper($targetClass);
        $sql = self::makeSelectOne($mapper, $this->sqlMode);
        $stmt = $this->prepare($sql, $targetClass);
        $stmt->execute([$id]);
        return $stmt;
    }

    /**
     * Persist a DTO with a defined id-column
     * This method can deduce if an UPDATE or an INSERT is needed.
     *
     * @template T
     * @param    T             $obj
     * @return   bool
     */
    public function persist(object $obj): bool
    {
        /** @var class-string<T> */
        $className = get_class($obj);
        $mapper = $this->mapperList->getMapper($className);

        if (is_null($mapper->getUniqueField())) {
            throw new DtoException(sprintf(
                "[oor4enaoR] %s does not have a unique field. Cannot use persist()",
                $mapper->getTableName(),
            ));
        }

        $idProperty = $mapper->getUniqueProperty();
        $idField = $mapper->getUniqueField();

        $id = $obj->$idProperty;

        $fields = $mapper->intoAssoc($obj);

        // Is the id set?
        if ($id) {
            // We have an id supplied
            $checkExistsSql = self::makeCountByIdColumn($mapper, $this->sqlMode);
            $stmt = $this->prepare($checkExistsSql, NumRecords::class);
            $stmt->execute([$id]);
            $numRecords = $stmt->fetch();

            if ($numRecords->numRecords > 0) {
                // We have a record.
                // Moving the id field to the end.
                unset($fields[$idField]);
                $fields[$idField] = $id;

                // and construct as an update
                $stmt = $this->prepare(
                    self::makeUpdate($mapper, $this->sqlMode),
                    $className,
                );
                return $stmt->execute(array_values($fields));
            } else {
                // We don't have a record already, but an id is set.
                // The scenario is likely that we have an uuid or something similar
                // as the primary id.
                // Construct as an insert.
                $stmt = $this->prepare(
                    self::makeInsertWithId($mapper, $this->sqlMode),
                    $className,
                );
                return $stmt->execute(array_values($fields));
            }
        } else {
            // If not, then we are inserting.
            // The id field is NULL, so we have to remove it from the record to allow
            // auto increment to do it's work.
            unset($fields[$idField]);
            $stmt = $this->prepare(
                self::makeInsertWithoutId($mapper, $this->sqlMode),
                $className,
            );
            $success = $stmt->execute(array_values($fields));

            // When we have inserted a new DTO, we assign the newly created id to
            // our DTO so it can be persisted again if need be.
            // The most common case if for the DTO id to be int, but the
            // PDO::lastInsertId() returns string, so we allow for int or string.
            $lastInsertId = $this->pdo->lastInsertId();
            $obj->$idProperty = match ($mapper->getUniquePropertyType()) {
                'int' => intval($lastInsertId),
                'string' => $lastInsertId,
                default => throw new DtoException(sprintf(
                    '[uuceiJ4eg] ID of type %s on DTO %s:%s is not supported',
                    $mapper->getUniquePropertyType(),
                    $className,
                    $idProperty,
                )),
            };
            return $success;
        }
    }

    /**
     * Insert a DTO that is assumed to reflect a table.
     * Most often a DTO that completely reflect a single table, you should consider
     * use the Connection::persist()-method instead.
     *
     * @template T
     * @param    T             $obj
     * @return   bool
     */
    public function insert(object $obj): bool
    {
        /** @var class-string<T> */
        $className = get_class($obj);
        $mapper = $this->mapperList->getMapper($className);
        $stmt = $this->prepare(
            self::makeInsertWithId($mapper, $this->sqlMode),
            $className,
        );
        return $stmt->execute(array_values($mapper->intoAssoc($obj)));
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
