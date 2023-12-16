<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlDirective;

final class ExportDirective extends GraphQlDirective
{
    protected function arguments(TypeRegistry $registry): array
    {
        return [
            InputField::withName('as')
                ->ofType(new NonNull($registry->string()))
                ->withDescription('The key of the variable to export.'),
            InputField::withName('isList')
                ->ofType($registry->boolean()),
        ];
    }

    protected function locations(): array
    {
        return [
            DirectiveLocation::FIELD,
        ];
    }

    protected function description(): string
    {
        return 'Export a value as a variable for a multi query.';
    }
}