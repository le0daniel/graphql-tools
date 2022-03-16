<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class InputField
{
    use DefinesField, DefinesReturnType;

    protected mixed $defaultValue = null;

    final public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): static {
        return new static($name);
    }

    final public function withDefaultValue(mixed $defaultValue): self {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    final public function toInputFieldDefinitionArray(TypeRepository $repository): ?array
    {
        if ($this->hideBecauseOfDeprecation()) {
            return null;
        }

        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->resolveReturnType($repository),
            'defaultValue' => $this->defaultValue,
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
        ];
    }
}