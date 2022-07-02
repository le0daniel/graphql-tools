<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Fields;

final class InputField
{
    use DefinesField, DefinesReturnType, DefinesDefaultValue, DefinesMetadata;

    final public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self
    {
        return new self($name);
    }

    final public function isHidden(): bool {
        return $this->hideFieldBecauseDeprecationDateIsPassed();
    }

    final public function toDefinition(TypeRegistry $typeRegistry): array
    {
        $defaultValue = isset($this->defaultValue)
            ? ['defaultValue' => $this->defaultValue]
            : [];

        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->resolveReturnType($typeRegistry),
            'deprecatedReason' => $this->computeDeprecationReason(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ] + $defaultValue;
    }
}