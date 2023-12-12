<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Utility\Types;
use RuntimeException;
use Throwable;

/**
 * @internal
 */
final class PartialPrintRegistry extends FactoryTypeRegistry
{
    private array $partialMocks = [];

    /** @var array<string,GraphQlTypes> */
    private array $typeHints = [];

    public function type(string $nameOrAlias, ?GraphQlTypes $typeHint = null): Closure
    {
        $typeName = $this->resolveAliasToName($nameOrAlias);
        if ($typeHint) {
            $this->typeHints[$typeName] = $typeHint;
        }
        return parent::type($typeName, $typeHint);
    }

    protected function getType(string $typeName): Type
    {
        try {
            return parent::getType($typeName);
        } catch (Throwable) {
            return $this->getMock($this->resolveAliasToName($typeName));
        }
    }

    private function getMock(string $typeName): Type
    {
        return $this->partialMocks[$typeName] ??= $this->createMock($typeName);
    }

    /**
     * @param array<Closure> $factories
     * @return array<Field|InputField>
     */
    private function extensionFactoriesToFields(array $factories): array {
        return array_reduce(
            $factories,
            fn(array $carry, Closure $factory) => [...$carry, ...$factory($this)],
            []
        );
    }

    /**
     * @param array<Field|InputField> $fields
     * @return array<array|FieldDefinition>
     * @throws DefinitionException
     */
    private function fieldsToDefinition(array $fields): array {
        return array_map(
            fn(Field|InputField $field): array|FieldDefinition => $field
                ->withDescription("@extend(): This field is an extension of an external type")
                ->toDefinition($this->schemaRules),
            $fields
        );
    }

    private function createMock(string $typeName): Type
    {
        $typeHint = $this->typeHints[$typeName] ?? GraphQlTypes::SCALAR;
        $isInterface = $typeHint === GraphQlTypes::INTERFACE;

        $pipes = [
            $this->extensionFactoriesToFields(...),
            $this->fieldsToDefinition(...),
            array_filter(...),
        ];

        $config = [
            'name' => $typeName,
            'fields' => array_reduce(
                $pipes,
                fn(mixed $carry, Closure $pipe): mixed => $pipe($carry),
                $this->getFieldExtensionsForTypeName($typeName)
            ),
            'description' => $isInterface
                ? 'External type, scalar, union or input type reference not present in the schema'
                : 'External type, interface, scalar, union or input type reference not present in the schema',
        ];

        return $isInterface
            ? new InterfaceType($config)
            : new ObjectType($config);
    }

    public function getTypeNames(): array
    {
        if (!empty($this->typeInstances)) {
            throw new RuntimeException("You can only use this before any type is initialized. Otherwise side effects occur.");
        }

        foreach (array_keys($this->typeFactories) as $name) {
            // We initialize all types and get their interfaces to ensure type hints are given.
            $type = $this->type($name)();
            if ($type instanceof ObjectType) {
                $type->getInterfaces();
            }
        }
        $this->typeInstances = [];
        $this->partialMocks = [];

        return array_map($this->resolveAliasToName(...), array_keys($this->typeFactories));
    }

    protected function resolveAliasToName(string $nameOrAlias): string
    {
        $typeName = parent::resolveAliasToName($nameOrAlias);
        if (str_contains($typeName, '\\')) {
            return Types::inferNameFromClassName($typeName);
        }
        return $typeName;
    }

}