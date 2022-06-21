<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Helper\Context;
use GraphQlTools\Definition\GraphQlUnion;

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
