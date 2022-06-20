<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlInterface;

final class MamelInterface extends GraphQlInterface {

    protected function fields(): array {
        return [
            Field::withName('sound')->ofType(Type::nonNull(Type::string())),
        ];
    }

    protected function description(): string {
        return '';
    }

    protected function resolveToType(mixed $typeValue, Context $context, ResolveInfo $info): string {
        switch ($typeValue['type']) {
            case 'lion':
                return LionType::class;
            case 'tiger':
                return TigerType::class;
        }

        throw new \RuntimeException('Could not match type: ' . $typeValue['type']);
    }
}
