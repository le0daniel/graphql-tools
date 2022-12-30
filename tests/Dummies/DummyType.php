<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class DummyType extends GraphQlType {

    protected function fields(TypeRegistry $registry): array{
        return [
            Field::withName('id')->ofType(Type::id())
        ];
    }

    protected function description(): string{
        return 'dummy';
    }

    public static function typeName(): string{
        return 'Dummy';
    }
}
