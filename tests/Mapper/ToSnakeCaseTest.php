<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Mapper;

use Dschledermann\Dto\Mapper\Key\ToSnakeCase;
use PHPUnit\Framework\TestCase;

class ToSnakeCaseTest extends TestCase
{
    public function testSimpleField(): void
    {
        $toSnakeCase = new ToSnakeCase();

        $str = 'thisIsASimpleField';

        $this->assertSame(
            'this_is_a_simple_field',
            $toSnakeCase->getFieldName($str),
        );
    }

    public function testSimpleClassName(): void
    {
        $toSnakeCase = new ToSnakeCase();

        $str = 'SomeClassName';

        $this->assertSame(
            'some_class_name',
            $toSnakeCase->getFieldName($str),
        );
    }
}
