<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class InputField
{
    use HasDescription, HasType;

    protected mixed $defaultValue;

    public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self {
        return new self($name);
    }

    final public function withDefaultValue(mixed $defaultValue): self {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function toInputFieldDefinition(TypeRepository $repository): array
    {
        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->resolveType($repository),
            'defaultValue' => $this->defaultValue,
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
        ];
    }
}