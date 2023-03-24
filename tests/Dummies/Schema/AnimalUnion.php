<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Definition\GraphQlUnion;
use RuntimeException;

final class AnimalUnion extends GraphQlUnion {

    protected function possibleTypes(): array {
        return [
            TigerType::class,
            LionType::class,
        ];
    }

    protected function description(): string {
        return 'Animals';
    }

    public static function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string {
        switch ($typeValue['type']) {
            case 'lion':
                return LionType::class;
            case 'tiger':
                return TigerType::class;
        }

        throw new RuntimeException('Could not match type: ' . $typeValue['type']);
    }
}
