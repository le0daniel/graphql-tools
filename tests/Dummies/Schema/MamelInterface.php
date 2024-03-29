<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlInterface;
use RuntimeException;

final class MamelInterface extends GraphQlInterface {

    protected function fields(TypeRegistry $registry): array {
        return [
            Field::withName('sound')->ofType(new NonNull($registry->string())),
        ];
    }

    public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string {
        switch ($typeValue['type']) {
            case 'lion':
                return LionType::class;
            case 'tiger':
                return TigerType::class;
        }

        throw new RuntimeException('Could not match type: ' . $typeValue['type']);
    }
}
