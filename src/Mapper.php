<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

use Dschledermann\Dto\Mapper\Ignore;
use Dschledermann\Dto\Mapper\Key\KeyMapperInterface;
use Dschledermann\Dto\Mapper\Key\ToSnakeCase;
use Dschledermann\Dto\Mapper\UniqueIdentifier;
use Dschledermann\Dto\Mapper\Value\FromPhp\FromPhpInterface;
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
        private ?string $uniqueField,
        private ?string $uniqueProperty,
        /** @var ReflectionProperty[] */
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
        $uniqueField = null;
        $uniqueProperty = null;
        foreach ($reflector->getProperties() as $property) {
            $hasIgnore = $property->getAttributes(Ignore::class);
            if ($hasIgnore) {
                continue;
            }

            $fieldNameMapper = $defaultMapper;
            foreach($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof KeyMapperInterface) {
                    $fieldNameMapper = $instance;
                }
            }

            $fieldName = $fieldNameMapper->getFieldName($property->getName());

            $isUniqueField = $property->getAttributes(UniqueIdentifier::class);
            if ($isUniqueField) {
                $uniqueProperty = $property->getName();
                $uniqueField = $fieldName;
            }

            $propertyMap[$fieldName] = $property;
        }

        return new Mapper(
            $reflector,
            $tableName,
            $uniqueField,
            $uniqueProperty,
            $propertyMap,
        );
    }

    public function getUniqueField(): ?string
    {
        return $this->uniqueField;
    }

    public function getUniqueProperty(): ?string
    {
        return $this->uniqueProperty;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldNames(): array
    {
        return array_keys($this->propertyMap);
    }

    /**
     * @param array $values
     * @return T
     */
    public function makeFromAssoc(array $values): object
    {
        $instance = $this->reflector->newInstanceWithoutConstructor();

        foreach ($this->propertyMap as $key => $property) {
            if (array_key_exists($key, $values)) {
                $property->setValue($instance, $values[$key]);
            } else {
                // Missing field? Is it nullable?
                if ($property->getType()->allowsNull()) {
                    $property->setValue($instance, null);
                } else {
                    throw new DtoException(sprintf(
                        '[eiiaNg9ph] The field %s was missing from the record set then creating a %s',
                        $key,
                        $this->reflector->getName(),
                    ));
                }
            }
        }

        return $instance;
    }

    public function makeAssocFromObject(object $obj): array
    {
        $result = [];
        foreach ($this->propertyMap as $key => $property) {
            $result[$key] = $property->getValue($obj);
        }
        return $result;
    }

    private function getFromPhp(ReflectionProperty $property): ?FromPhpInterface
    {
        foreach($property->getAttributes() as $attribute) {
        }
    }        
}
