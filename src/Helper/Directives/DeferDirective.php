<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlDirective;

final class DeferDirective extends GraphQlDirective
{
    protected function arguments(TypeRegistry $registry): array
    {
        return [
            InputField::withName('if')
                ->ofType($registry->boolean())
                ->withDefaultValue(true),
            InputField::withName('label')
                ->ofType($registry->string()),
        ];
    }

    protected function locations(): array
    {
        return [DirectiveLocation::FIELD, DirectiveLocation::INLINE_FRAGMENT, DirectiveLocation::FRAGMENT_SPREAD];
    }

    protected function description(): string
    {
        return 'Defer the resolution of this field for later.';
    }
}