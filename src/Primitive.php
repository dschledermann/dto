<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

/**
 * @template T
 */
final class Primitive
{
    const BOOLEAN = 'bool';
    const FLOAT = 'float';
    const INTEGER = 'int';
    const STRING = 'string';

    private function __construct(private string $type)
    {}

    /**
     * @template T
     * Attempt to create a primitive mapper
     *
     * @param class-string<T>  $type  The type we are attempting to use.
     * @return Primitive<T>|null      A primitive mapper or null if it's not a
     *                                recognized primitive type.
     */
    public static function create(string $type): ?Primitive
    {
        switch ($type) {
        case self::BOOLEAN:
        case self::FLOAT:
        case self::INTEGER:
        case self::STRING:
            return new Primitive($type);
        }

        return null;
    }

    /**
     * Convert a result row to the primitive type
     *
     * @param array  $row  Result row to cast.
     * @return T           The converted value. This amounts to get the first value
     *                     of the array and type cast it to the desired value.
     */
    public function castResult(array $row): mixed
    {
        $value = array_shift($row);
        return match ($this->type) {
            self::BOOLEAN => boolval($value),
            self::FLOAT => floatval($value),
            self::INTEGER => intval($value),
            self::STRING => '' . $value,
        };
    }
}
