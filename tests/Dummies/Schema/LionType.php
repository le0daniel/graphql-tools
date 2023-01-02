<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Context;
use GraphQlTools\Test\Dummies\Enum\Eating;
use GraphQlTools\Helper\Compilation\ClosureCompiler as C;

final class LionType extends GraphQlType {

    public function __construct()
    {
        $this->removalDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-01-10 00:00:00');
        $this->deprecationReason = 'Because it is deprecated! Deal with it!';
    }

    public function testMyDependencies(string $string, Context $context, ?ResolveInfo $info): string {
        $className = static::class ?? self::class;
        $clusure = function () use ($className) {
            return "name is: {$className}";
        };
        $resolveInfo = ResolveInfo::class;

        return "The string is: {$string}: {\$this->name}: {$className} as {$clusure()} with {$resolveInfo}";
    }

    protected function fields(TypeRegistry $registry): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn($data) => $data['sound']),

            Field::withName('fieldWithMeta')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(function ($value, $args, $context, ResolveInfo $resolveInfo) {
                    return "policy is: " . $resolveInfo->fieldDefinition->config['metadata']['policy'];
                })
                ->withArguments(
                    InputField::withName('test')
                        ->ofType(Type::string())
                        ->withDefaultValue('This is a string'),
                    InputField::withName('else')
                        ->ofType(Type::string())
                        ->withDefaultValue(Eating::MEAT)
                )
                ->deprecated('Some reason', \DateTime::createFromFormat('Y-m-d H:i:s', '2023-01-09 10:00:10'))
                ->withMetadata([
                    'policy' => 'This is my special policy'
                ]),

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
