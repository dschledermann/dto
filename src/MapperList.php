<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

final class MapperList
{
    /** @var array<string, Mapper> */
    private array $mappers = [];

    /**
     * @template T
     * @param class-string<T> $targetClass
     * @return Mapper<T>
     */
    public function getMapper(string $targetClass): Mapper
    {
        if (!array_key_exists($targetClass, $this->mappers)) {
            $this->mappers[$targetClass] = Mapper::create($targetClass);
        }
        return $this->mappers[$targetClass];

    }
}
