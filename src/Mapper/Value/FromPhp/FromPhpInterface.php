<?php

declare(strict_types=1);

namespace Dschledermann\Dto\Mapper\Value\FromPhp;

interface FromPhpInterface
{
    /**
     * When a field is persisted into the database, you can transform it into a type
     * relevant fitting for the database field.
     * @param mixed $value   Value from the PHP object.
     * @return mixed         Value written to the database.
     */
    public function fromPhpValue(mixed $value): mixed;
}
