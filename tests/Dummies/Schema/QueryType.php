<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;

final class QueryType extends GraphQlType {

    protected function fields(): array {
        return [
            'whoami' => [
                'type' => Type::string(),
                'resolve' => fn() => 'This is a test'
            ]
        ];
    }

    protected function description(): string {
        return '';
    }
}
