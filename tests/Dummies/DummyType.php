<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;

final class DummyType extends GraphQlType {

    protected function fields(): array{
        return [
            'id' => Type::id()
        ];
    }

    protected function description(): string{
        return 'dummy';
    }

    public static function typeName(): string{
        return 'Dummy';
    }
}
