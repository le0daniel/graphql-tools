<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use PHPUnit\Framework\TestCase;

abstract class TypeTestCase extends TestCase
{

    abstract public function typeClassName(): string;



}