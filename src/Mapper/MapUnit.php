<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper;

use Dschledermann\Dto\Mapper\Value\FromPhpInterface;
use Dschledermann\Dto\Mapper\Value\IntoPhpInterface;
use ReflectionProperty;

final class MapUnit
{
    public function __construct(
        public string $keyName,
        public string $propertyName,
        public ReflectionProperty $property,
        public ?FromPhpInterface $fromPhp = null,
        public ?IntoPhpInterface $intoPhp = null,
    ) {}
}
