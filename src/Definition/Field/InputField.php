<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class InputField
{
    use DefinesField, DefinesReturnType, DefinesDefaultValue;

    public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self {
        return new self($name);
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