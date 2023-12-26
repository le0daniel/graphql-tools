<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema\Types;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class TagType extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('value')
                ->ofType($registry->string())
                ->middleware(function(string $tag, $a, $b, $c, $next) {
                    return $next(strtoupper($tag), $a, $b, $c);
                })
                ->resolvedBy(fn(string $tag): string => $tag),
        ];
    }
}