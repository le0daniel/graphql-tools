<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Utility\Types;

abstract class GraphQlType implements DefinesGraphQlType
{
    use HasDescription, HasDeprecation, InitializesFields;

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]|array<string, callable(string, TypeRegistry): Field>
     */
    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry, array $injectedFieldFactories = [], array $excludeFieldsWithTags = []): ObjectType {
        return new ObjectType(
            [
                'name' => $this->getName(),
                'description' => $this->addDeprecationToDescription($this->description()),
                'deprecationReason' => $this->deprecationReason(),
                'removalDate' => $this->removalDate(),
                'fields' => fn() => $this->initializeFields(
                    $registry,
                    [$this->fields(...), ...$injectedFieldFactories],
                    $excludeFieldsWithTags
                ),
                'interfaces' => fn() => array_map(
                    fn(string $interfaceName) => $registry->type($interfaceName),
                    $this->interfaces()
                ),
            ]
        );
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array
    {
        return [];
    }

}
