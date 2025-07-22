<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use Dschledermann\Dto\Query\MakeCountByIdColumnTrait;
use Dschledermann\Dto\Query\MakeInsertTrait;
use Dschledermann\Dto\Query\MakeSelectOneTrait;
use Dschledermann\Dto\Query\MakeUpdateTrait;
use PDO;

class Connection
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

    /**
     * Create a Connection from an already connected PDO.
     *
     * @param PDO      $pdo      An already connected PDO.
     * @param SqlMode  $sqlMode  The SQL mode.
     * @return static
     */
    public static final function createFromPdo(
        PDO $pdo,
        SqlMode $sqlMode = SqlMode::MySQL,
    ): static
    {
        return new static($pdo, $sqlMode, new MapperList());
    }

    /**
     * Create a Connection from a the URL in named environment variable.
     *
     * @param array    $pdoParams   Params passed to the PDO creation.
     * @param string   $varName     Name of environment variable holding the URL.
     * @return static
     */
    public static final function createFromEnv(
        array $pdoParams = [],
        string $varName = 'DATABASE_URL',
    ): static
    {
        $url = getenv($varName);
        return static::createFromUrl($url, $pdoParams);
    }

    /**
     * Create a Connection from a provided URL.
     *
     * @param string  $url        DATABASE_URL format url string for connection.
     * @param array   $pdoParams  Params passed to the PDO creation.
     * @return static
     */
    public static final function createFromUrl(string $url, array $pdoParams): static
    {
        $url = parse_url($url);
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

        return new static($pdo, $sqlMode, new MapperList());
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
     * Persist a DTO that is assumed to reflect a table.
     * This method can deduce if an UPDATE or an INSERT is needed, but it has a bit
     * more database overhead than ::insert() or ::update().
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

        $idField = $mapper->getUniqueField();

        if (is_null($idField)) {
            throw new DtoException(sprintf(
                "[oor4enaoR] %s does not have a unique field. Cannot use persist()",
                $mapper->getTableName(),
            ));
        }

        $id = $mapper->getUniqueValue($obj);
        $fields = $mapper->intoAssoc($obj);

        // Is the id set?
        if ($id) {
            // We have an id supplied
            $checkExistsSql = self::makeCountByIdColumn($mapper, $this->sqlMode);
            $stmt = $this->prepare($checkExistsSql, Primitive::INTEGER);
            $stmt->execute([$id]);
            $numRecords = $stmt->fetch();

            if ($numRecords > 0) {
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
            // auto increment to do its work.
            unset($fields[$idField]);
            $stmt = $this->prepare(
                self::makeInsertWithoutId($mapper, $this->sqlMode),
                $className,
            );
            $success = $stmt->execute(array_values($fields));

            // When we have inserted a new DTO, we assign the newly created id to
            $mapper->setUniqueValue($obj, $this->pdo->lastInsertId());
            return $success;
        }
    }

    /**
     * Insert a DTO that is assumed to reflect a table.
     * Use this if you know for certain that you are dealing with a new instance.
     * If you are not sure, then use the ::persist()-method.
     * You can use this method with types without a defined id field.
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

        $values = $mapper->intoAssoc($obj);
        $uniqueField = $mapper->getUniqueField();

        // Do we have a unique field AND the unique field is null
        if ($uniqueField && is_null($values[$uniqueField])) {
            // Then we are dealing with an autoincrement field
            // Unset it and do an insert without it.
            unset($values[$uniqueField]);
            $stmt = $this->prepare(
                self::makeInsertWithoutId($mapper, $this->sqlMode),
                $className,
            );
            $success = $stmt->execute(array_values($values));
            $mapper->setUniqueValue($obj, $this->pdo->lastInsertId());
            return $success;
        } else {
            // Insert the whole thing
            $stmt = $this->prepare(
                self::makeInsertWithId($mapper, $this->sqlMode),
                $className,
            );
            return $stmt->execute(array_values($values));
        }

    }

    /**
     * Update a DTO that is assumed to reflect a table.
     * Use this if you know for certain that you are dealing with an existing instance.
     * If you are not sure, then use the ::persist()-method.
     * This method requires a defined id-column and that the column has a non-null
     * value.
     *
     * @template T
     * @param    T           $obj
     * @return   bool
     */
    public function update(object $obj): bool
    {
        /** @var class-string<T> */
        $className = get_class($obj);
        $mapper = $this->mapperList->getMapper($className);

        $uniqueField = $mapper->getUniqueField();
        if (is_null($uniqueField)) {
            throw new DtoException(sprintf(
                '[Eg7Ahdoh9] Cannot construct update as "%s" does not a unique identifier.',
                $className,
            ));
        }

        $id = $mapper->getUniqueValue($obj);
        if (is_null($id)) {
            throw new DtoException(sprintf(
                '[eiK9Aegai] Cannot update as the "%s::%s" field is null',
                $className,
                $uniqueField,
            ));
        }

        // Move id to last field
        $values = $mapper->intoAssoc($obj);
        unset($values[$uniqueField]);
        $values[$uniqueField] = $id;

        $stmt = $this->prepare(
            self::makeUpdate($mapper, $this->sqlMode),
            $className,
        );
        return $stmt->execute(array_values($values));
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
