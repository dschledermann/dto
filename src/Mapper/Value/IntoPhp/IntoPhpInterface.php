<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value\IntoPhp;

interface IntoPhpInterface
{
    /**
     * When a field is fetched from the database, you can transform it into a type
     * relevant in PHP.
     * @param mixed $value   Value from the database.
     * @return mixed         Value populated into the PHP object.
     */
    public function intoPhpValue(mixed $value): mixed;
}
