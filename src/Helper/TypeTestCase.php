<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use PHPUnit\Framework\TestCase;

abstract class TypeTestCase extends TestCase
{
    abstract protected function typeClassName(): string;

    final protected function field(string $name): FieldTestCase
    {
        return new FieldTestCase($this->typeClassName(), $name);
    }

}