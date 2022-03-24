<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class InputField
{
    use DefinesField, DefinesReturnType, DefinesMetadata;

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
        if ($this->hideBecauseOfDeprecation() || $repository->shouldHideInputField($this->isBeta, $this->metadata)) {
            return null;
        }

        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->resolveReturnType($repository),
            'defaultValue' => $this->defaultValue,
            'deprecatedReason' => $this->computeDeprecationReason(),
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ];
    }
}