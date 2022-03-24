<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use Exception;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\HolderDummy;

final class TigerType extends GraphQlType {

    protected function fields(): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->mappedBy(fn(array $data) => $data['sound']),

            Field::withName('withArg')
                ->ofType(Type::string())
                ->withArguments(
                    Argument::withName('test')
                        ->ofType(Type::string())
                        ->withValidator(static function (mixed $argument) {
                            return $argument ?? throw new Exception('Failed');
                        })
                )
                ->mappedBy(fn($tiger, array $arguments) => $arguments['test']),

            Field::withName('deferred')
                ->ofType(Type::string())
                ->resolveData(function (array $items, array $arguments, Context $context){
                    return [
                        2 => 'My Deferred',
                        3 => 'Second Deferred'
                    ];
                })
                ->mappedBy(function(array $data, array $arguments, array $loadedData, Context $context) {
                    return $loadedData[$data['id']];
                }),

            Field::withName('fieldWithInjections')
                ->ofType(Type::string())
                ->resolveData(function(array $items, array $arguments, Context $context, HolderDummy $service){
                    return [$service->result];
                })
                ->mappedBy(fn($data, array $arguments, array $items) => $items[0])
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
