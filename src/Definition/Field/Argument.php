<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Shared\DefinesDefaultValue;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

final class Argument
{
    use DefinesReturnType, DefinesField, DefinesDefaultValue;

    /** @var callable|null */
    private $validator = null;

    private function __construct(public readonly string $name)
    {
    }

    public static function withName(string $name): self {
        return new self($name);
    }

    public function withValidator(callable $validator): self {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Validates an input value with a defined validator if set, otherwise everything is considered valid.
     *
     * @param mixed $value
     * @param array $allArguments
     * @return mixed
     */
    public function validateValue(mixed $value, array $allArguments): mixed
    {
        if (!$this->validator) {
            return $value;
        }

        return ($this->validator)($value, $allArguments);
    }

    final public function toInputFieldDefinitionArray(TypeRepository $repository): array {
        return [
            'name' => $this->name,
            'type' => $this->resolveReturnType($repository),
            'defaultValue' => $this->defaultValue,
            'description' => $this->computeDescription(),
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
        ];
    }

}