<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Test\Dummies\Schema\AnimalUnion;
use GraphQlTools\Test\Dummies\Schema\CreateAnimalInputType;
use GraphQlTools\Test\Dummies\Schema\EatingEnum;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\MamelInterface;
use GraphQlTools\Test\Dummies\Schema\TigerType;
use GraphQlTools\Utility\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{

    public function testInferNameFromClassName()
    {
        self::assertEquals('Animal', Types::inferNameFromClassName(AnimalUnion::class));
        self::assertEquals('Eating', Types::inferNameFromClassName(EatingEnum::class));
        self::assertEquals('Mamel', Types::inferNameFromClassName(MamelInterface::class));
        self::assertEquals('Tiger', Types::inferNameFromClassName(TigerType::class));
        self::assertEquals('Json', Types::inferNameFromClassName(JsonScalar::class));
        self::assertEquals('CreateAnimalInput', Types::inferNameFromClassName(CreateAnimalInputType::class));
    }
}
