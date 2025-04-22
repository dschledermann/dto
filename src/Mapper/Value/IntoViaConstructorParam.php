<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value;

use Attribute;
use ReflectionClass;

/**
 * @template T
 * This value mapper will take a value from the database and pass it as the first
 * parameter into the constructor of a given type T
 */
#[Attribute]
final class IntoViaConstructorParam implements IntoPhpInterface
{
    /**
     * @param class-string<T>
     */
    public function __construct(
        private string $targetClass,
    ) {}

    /**
     * @return T
     */
    public function intoPhpValue(mixed $value): mixed
    {
        $reflection = new ReflectionClass($this->targetClass);
        return $reflection->newInstance($value);
    }
}
