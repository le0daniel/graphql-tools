<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInterface implements DefinesGraphQlType
{
    use InitializesFields, HasDescription, HasDeprecation;

    private const CLASS_POSTFIX = 'Interface';

    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry, array $injectedFieldFactories = []): InterfaceType
    {
        return new InterfaceType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'fields' => fn() => $this->initializeFields(
                $registry,
                [$this->fields(...), ...$injectedFieldFactories],
            ),
            'resolveFn' => [static::class, 'resolveToType'],
            'resolveType' => fn($_, OperationContext $context, $info) => $registry->type(
                static::resolveToType($_, $context->context, $info)
            ),
        ]);
    }

    public function getName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

    abstract public static function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;
}
