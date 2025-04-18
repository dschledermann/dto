<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

trait GetMapperTrait
{
    /** @var array<class-string, Mapper> */
    private static array $mappers = [];

    protected static function getMapper(string $targetClass): Mapper
    {
        if (!array_key_exists($targetClass, static::$mappers)) {
            static::$mappers[$targetClass] = Mapper::create($targetClass);
        }
        return static::$mappers[$targetClass];
    }
}
