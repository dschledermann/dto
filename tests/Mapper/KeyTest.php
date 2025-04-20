<?php

declare(strict_types=1);

namespace Tests\Dschledermann\Dto\Mapper;

use Dschledermann\Dto\Mapper\Key\NoTranslation;
use Dschledermann\Dto\Mapper\Key\ToLowerCase;
use Dschledermann\Dto\Mapper\Key\ToSnakeCase;
use PHPUnit\Framework\TestCase;

class KeyTest extends TestCase
{
    public function testSnakeCase(): void
    {
        $toSnakeCase = new ToSnakeCase();

        $str = 'thisIsASimpleField';

        $this->assertSame(
            'this_is_a_simple_field',
            $toSnakeCase->getFieldName($str),
        );

        $str = 'SomeClassName';

        $this->assertSame(
            'some_class_name',
            $toSnakeCase->getFieldName($str),
        );
    }

    public function testToLowerCase(): void
    {
        $toLowerCase = new ToLowerCase();

        $this->assertSame(
            'thisislowercase',
            $toLowerCase->getFieldName("ThisIsLowerCase"),
        );
    }

    public function testNoTranslation(): void
    {
        $noTranslation = new NoTranslation();
        $this->assertSame("NoChange", $noTranslation->getFieldName("NoChange"));
    }
}
