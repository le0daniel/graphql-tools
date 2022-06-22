<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\TypeRegistry;

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

    final public function toDefinition(TypeRegistry $repository): ?array
    {
        if ($this->hideFieldBecauseDeprecationDateIsPassed() || $repository->shouldHideInputField($this)) {
            return null;
        }

        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->resolveReturnType($repository),
            'defaultValue' => $this->defaultValue,
            'deprecatedReason' => $this->computeDeprecationReason(),
        ];
    }
}