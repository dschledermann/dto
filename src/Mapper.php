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
use ReflectionProperty;

/**
 * @template T
 */
final class Mapper
{
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

    public function getUniqueProperty(): ?string
    {
        if ($this->uniqueProperty) {
            return $this->uniqueProperty->propertyName;
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
        $fieldNames = [];

        foreach ($this->propertyMap as $propertyUnit) {
            $fieldNames[] = $propertyUnit->keyName;
        }
        return $fieldNames;
    }

    /**
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

    public function intoAssoc(object $obj): array
    {
        $result = [];
        foreach ($this->propertyMap as $propertyUnit) {
            $result[$propertyUnit->keyName] = $propertyUnit->property->getValue($obj);
        }
        return $result;
    }

    private function getFromPhp(ReflectionProperty $property): ?FromPhpInterface
    {
        foreach($property->getAttributes() as $attribute) {
        }
    }
}
