<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use Dschledermann\Dto\Mapper\Ignore;
use Dschledermann\Dto\Mapper\Key\KeyMapperInterface;
use Dschledermann\Dto\Mapper\Key\ToSnakeCase;
use Dschledermann\Dto\Mapper\MapUnit;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Mapper\Value\FromPhpInterface;
use Dschledermann\Dto\Mapper\Value\IntoPhpInterface;
use ReflectionClass;

/**
 * @template T
 */
final class Mapper
{
    /**
     * @param ReflectionClass<T>  $reflector
     * @param string              $table
     * @param ?MapUnit            $uniqueProperty
     * @param MapUnit[]           $propertyMap
     */
    private function __construct(
        private ReflectionClass $reflector,
        private string $tableName,
        private ?MapUnit $uniqueProperty,
        /** @var MapUnit[] */
        private array $propertyMap,
    ) {}

    /**
     * @template T
     * @param class-string<T> $className
     * @return Mapper<T>
     */
    public static function create(string $className): Mapper
    {
        $reflector = new ReflectionClass($className);

        $tableName = str_replace(
            $reflector->getNamespaceName() . '\\',
            '',
            $reflector->getName(),
        );

        $defaultMapper = new ToSnakeCase();
        $tableNameRemapper = $defaultMapper;

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof KeyMapperInterface) {
                $tableNameRemapper = $instance;
            }
        }

        $tableName = $tableNameRemapper->getFieldName($tableName);

        $propertyMap = [];
        $uniqueProperty = null;

        foreach ($reflector->getProperties() as $property) {
            $hasIgnore = $property->getAttributes(Ignore::class);
            if ($hasIgnore) {
                continue;
            }

            $propertyName = $property->getName();

            $mapUnit = new MapUnit(
                $defaultMapper->getFieldName($propertyName),
                $propertyName,
                $property,
            );

            $propertyMap[] = $mapUnit;

            foreach($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof KeyMapperInterface) {
                    $mapUnit->keyName = $instance->getFieldName($propertyName);
                }

                if ($instance instanceof IntoPhpInterface) {
                    $mapUnit->intoPhp = $instance;
                }

                if ($instance instanceof FromPhpInterface) {
                    $mapUnit->fromPhp = $instance;
                }
            }

            if ($property->getAttributes(UniqueIdentifier::class)) {
                $uniqueProperty = $mapUnit;
            }
        }

        return new Mapper(
            $reflector,
            $tableName,
            $uniqueProperty,
            $propertyMap,
        );
    }

    public function getUniqueField(): ?string
    {
        if ($this->uniqueProperty) {
            return $this->uniqueProperty->keyName;
        } else {
            return null;
        }
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldNames(): array
    {
        return array_map(fn($e) => $e->keyName, $this->propertyMap);
    }

    /**
     * Get unique identifier if any exists on the object
     *
     * @param T  $obj
     * @return mixed
     */
    public function getUniqueValue(object $obj): mixed
    {
        if ($this->uniqueProperty) {
            return $this->uniqueProperty->property->getValue($obj);
        } else {
            throw new DtoException(sprintf(
                "[ohya9tu4X] '%s' does not have a unique property",
                $this->reflector->getName(),
            ));
        }
    }

    /**
     * Update the id value for an object
     *
     * @param T  $obj
     * @param mixed $idValue
     */
    public function setUniqueValue(object $obj, mixed $idValue): void
    {
        if ($this->uniqueProperty) {
            $myIdType = $this->uniqueProperty->property->getType()->getName();
            $givenIdType = gettype($idValue);
            $givenIdType = match ($givenIdType) {
                'integer' => 'int',
                default => $givenIdType,
            };
            if ($givenIdType === $myIdType) {
                $this->uniqueProperty->property->setValue($obj, $idValue);
            } elseif ($givenIdType === 'string' && $myIdType === 'int') {
                $this->uniqueProperty->property->setValue($obj, intval($idValue));
            } else {
                throw new DtoException(sprintf(
                    "[King4poo3] Unable to set unique value on '%s'."
                        . " ID is of type '%s' and a value of type '%s' was given",
                    $this->reflector->getName(),
                    $myIdType,
                    $givenIdType,
                ));
            }
        } else {
            throw new DtoException(sprintf(
                "[eNigah4bi] '%s' does not have a unique property",
                $this->reflector->getName(),
            ));
        }
    }

    /**
     * Convert assoc array into object of type T.
     *
     * @param array $values
     * @return T
     */
    public function fromAssoc(array $values): object
    {
        $instance = $this->reflector->newInstanceWithoutConstructor();

        foreach ($this->propertyMap as $propertyUnit) {
            if (array_key_exists($propertyUnit->keyName, $values)) {

                $value = $values[$propertyUnit->keyName];
                if ($intoPhp = $propertyUnit->intoPhp) {
                    $value = $intoPhp->intoPhpValue($value);
                }

                $propertyUnit->property->setValue($instance, $value);
            } else {
                // Missing field? Is it nullable?
                if ($propertyUnit->property->getType()->allowsNull()) {
                    $propertyUnit->property->setValue($instance, null);
                } else {
                    throw new DtoException(sprintf(
                        '[eiiaNg9ph] The field %s was missing from the record set then creating a %s',
                        $propertyUnit->keyName,
                        $this->reflector->getName(),
                    ));
                }
            }
        }

        return $instance;
    }

    /**
     * Convert object of type T into assoc array.
     *
     * @param  T  $obj
     * @return array
     */
    public function intoAssoc(object $obj): array
    {
        $result = [];
        foreach ($this->propertyMap as $propertyUnit) {
            $value = $propertyUnit->property->getValue($obj);

            if ($fromPhp = $propertyUnit->fromPhp) {
                $value = $fromPhp->fromPhpValue($value);
            }
            $result[$propertyUnit->keyName] = $value;
        }
        return $result;
    }
}
