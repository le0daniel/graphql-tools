<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Definition\Field\Shared\Deprecatable;

final class InputField implements DefinesGraphQlType
{
    use DefinesField, Deprecatable, DefinesReturnType, DefinesDefaultValue, DefinesMetadata;

    final public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self
    {
        return new self($name);
    }

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
            '__metadata' => $this->metadata,
        ] + $defaultValue;
    }
}