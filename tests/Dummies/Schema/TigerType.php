<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\DeferredField;
use GraphQlTools\Definition\GraphQlType;

final class TigerType extends GraphQlType {

    protected function fields(): array {
        return [
            'sound' => Type::nonNull(Type::string()),

            DeferredField::withName('deferred')
                ->withReturnType(Type::string())
                ->resolveAggregated(function (array $items, array $arguments){
                    return [
                        2 => 'My Deferred',
                        3 => 'Second Deferred'
                    ];
                })
                ->resolveItem(function(array $data, array $loadedData) {
                    return $loadedData[$data['id']];
                })
        ];
    }

    protected function interfaces(): array {
        return [
            MamelInterface::class
        ];
    }

    protected function description(): string {
        return '';
    }
}
