<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use Exception;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Data\Models\Holder;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\DeferredField;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\HolderDummy;

final class TigerType extends GraphQlType {

    protected function fields(): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn(array $data) => $data['sound'])
            ,

            Field::withName('withArg')
                ->ofType(Type::string())
                ->withArguments(
                    Argument::withName('test')
                        ->ofType(Type::string())
                        ->withValidator(static function (mixed $argument) {
                            return $argument ?? throw new Exception('Failed');
                        })
                )
                ->resolvedBy(fn($data, array $arguments) => $arguments['test']),

            DeferredField::withName('deferred')
                ->ofType(Type::string())
                ->resolveAggregated(function (array $items, array $arguments){
                    return [
                        2 => 'My Deferred',
                        3 => 'Second Deferred'
                    ];
                })
                ->resolveItem(function(array $data, array $loadedData) {
                    return $loadedData[$data['id']];
                }),

            DeferredField::withName('fieldWithInjections')
                ->ofType(Type::string())
                ->resolveAggregated(function(array $items, array $arguments, Context $context, HolderDummy $service){
                    return [$service->result];
                })
                ->resolveItem(fn($data, array $items) => $items[0])
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
