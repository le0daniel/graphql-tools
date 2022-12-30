<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\CanBeDeprecated;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Utility\Classes;

abstract class GraphQlType
{
    use HasDescription, CanBeDeprecated, InitializesFields;

    private const CLASS_POSTFIX = 'Type';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]|callable[]
     */
    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry): ObjectType {
        return new ObjectType(
            [
                'name' => static::typeName(),
                'description' => $this->addDeprecationToDescription($this->description()),
                'fields' => fn() => $this->initializeFields($registry, $this->fields($registry), true),
                'interfaces' => fn() => array_map(
                    fn(string $interfaceName) => $registry->type($interfaceName),
                    $this->interfaces()
                ),
                'deprecationReason' => $this->deprecationReason,
                'removalDate' => $this->removalDate,
            ]
        );
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array
    {
        return [];
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
