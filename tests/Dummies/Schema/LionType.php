<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use DateTime;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Context;
use GraphQlTools\Test\Dummies\Enum\Eating;

final class LionType extends GraphQlType {

    public function __construct()
    {
        $this->removalDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-01-10 00:00:00');
        $this->deprecationReason = 'Because it is deprecated! Deal with it!';
    }

    protected function fields(TypeRegistry $registry): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->middleware(
                    fn(array $data, $args, $context, $info, $next) => $next($data['sound'] ?? '', $args, $context, $info)
                )
                ->resolvedBy(fn(string $sound) => $sound),

            Field::withName('fieldWithMeta')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(function ($value, $args, $context, ResolveInfo $resolveInfo) {
                    return "Tags are: " . implode(', ', $resolveInfo->fieldDefinition->config['tags'] ?? []);
                })
                ->tags('First', 'Second')
                ->withArguments(
                    InputField::withName('test')
                        ->ofType(Type::string())
                        ->withDefaultValue('This is a string'),
                    InputField::withName('else')
                        ->ofType(Type::string())
                        ->withDefaultValue(Eating::MEAT->name)
                )
                ->deprecated('Some reason', DateTime::createFromFormat('Y-m-d H:i:s', '2023-01-09 10:00:10')),

            // Field::withName('myEnum')
            //     ->ofType($registry->type(EatingEnum::class))
            //     ->resolvedBy(fn() => Eating::MEAT),
        ];
    }

    protected function interfaces(): array {
        return [MamelInterface::class];
    }

    protected function description(): string {
        return '';
    }

    protected function metadata(): array
    {
        return [
            'policies' => [
                'mamel:read' => 'Must have the scope: `mamel:read` to access this property'
            ],
        ];
    }
}
