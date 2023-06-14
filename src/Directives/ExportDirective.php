<?php declare(strict_types=1);

namespace GraphQlTools\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlDirective;

final class ExportDirective extends GraphQlDirective
{
    public const NAME = 'export';

    protected function arguments(): array
    {
        return [
            InputField::withName('as')
                ->ofType(Type::nonNull(Type::string()))
                ->withDescription('The key of the variable to export.'),
            InputField::withName('isList')
                ->ofType(Type::boolean()),
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

    public function getName(): string
    {
        return self::NAME;
    }
}