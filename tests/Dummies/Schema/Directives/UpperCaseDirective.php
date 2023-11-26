<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Directives;

use Closure;
use GraphQL\Language\DirectiveLocation;
use GraphQlTools\Contract\FieldMiddleware;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlDirective;

final class UpperCaseDirective extends GraphQlDirective implements FieldMiddleware
{
    protected function arguments(TypeRegistry $registry): array
    {
        return [
            InputField::withName('if')
                ->ofType($registry->nonNull($registry->boolean()))
                ->withDefaultValue(true)
        ];
    }

    protected function locations(): array
    {
        return [DirectiveLocation::FIELD];
    }

    protected function description(): string
    {
        return '';
    }

    public static function createMiddleware(array $arguments): ?Closure
    {
        return function ($data, $args, $context, $info, $next) use ($arguments) {
            $value = $next($data, $args, $context, $info);
            assert(is_string($value));
            return $arguments['if'] ? strtoupper($value) : $value;
        };
    }
}