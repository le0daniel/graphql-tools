<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use Exception;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\Context;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;

final class TigerType extends GraphQlType {

    protected function fields(TypeRegistry $registry): array {
        return [
            Field::withName('sound')
                ->ofType(new NonNull($registry->string()))
                ->resolvedBy(fn(array $data) => $data['sound']),

            Field::withName('withArg')
                ->ofType($registry->string())
                ->withArguments(
                    InputField::withName('test')
                        ->ofType($registry->string())
                )
                ->resolvedBy(fn($tiger, array $arguments) => $arguments['test']),

            Field::withName('deferred')
                ->ofType($registry->string())
                ->resolvedBy(function(array $data, array $arguments, $context, ResolveInfo $resolveInfo) {
                    return $context->dataLoader('test', $arguments, $resolveInfo)
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
