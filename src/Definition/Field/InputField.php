<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesDescription;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Definition\Field\Shared\DefinesTags;
use GraphQlTools\Definition\Field\Shared\Deprecatable;

final class InputField implements DefinesGraphQlType
{
    use DefinesDescription, Deprecatable, DefinesReturnType, DefinesDefaultValue, DefinesTags;

    final public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self
    {
        return new self($name);
    }

    /**
     * @internal This is used internally to get the state of the builder. Do not use this.
     * @param TypeRegistry $typeRegistry
     * @return array
     * @throws \GraphQlTools\Definition\DefinitionException
     */
    final public function toDefinition(TypeRegistry $typeRegistry): array
    {
        $defaultValue = isset($this->defaultValue)
            ? ['defaultValue' => $this->defaultValue]
            : [];

        return [
            'name' => $this->name,
            'description' => $this->addDeprecationToDescription($this->description ?? ''),
            'type' => $this->resolveReturnType($typeRegistry),
            'deprecatedReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'tags' => $this->getTags(),
        ] + $defaultValue;
    }
}