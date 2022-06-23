<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Test\Dummies\Enum\Eating;

class EatingEnum extends GraphQlEnum
{

    protected function values(): array|string
    {
        return Eating::class;
    }

    protected function description(): string
    {
        return '';
    }
}