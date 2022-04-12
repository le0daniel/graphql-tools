<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class TigerType extends GraphQlType {

    protected function fields(): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn(array $data) => $data['sound']),

            Field::withName('withArg')
                ->ofType(Type::string())
                ->withArguments(
                    Argument::withName('test')
                        ->ofType(Type::string())
                        ->withValidator(static function (mixed $argument) {
                            return $argument ?? throw new Exception('Failed');
                        })
                )
                ->resolvedBy(fn($tiger, array $arguments) => $arguments['test']),

            Field::withName('deferred')
                ->ofType(Type::string())
                ->resolvedBy(function(array $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
                    return $context->withDataLoader('test', $arguments, $resolveInfo)
                        ->load($data['id']);
                }),
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
