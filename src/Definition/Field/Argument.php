<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\TypeRepository;

final class Argument
{
    /** @var Type|callable */
    private $type;

    private ?string $description = null;
    private mixed $defaultValue = null;

    /** @var callable|null */
    private $validator = null;

    private function __construct(public readonly string $name)
    {
    }

    public static function withName(string $name): self {
        return new self($name);
    }

    public function ofType(Type|callable $type): self {
        $this->type = $type;
        return $this;
    }

    public function withDefaultValue(mixed $defaultValue): self {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function withDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public function withValidator(callable $validator): self {
        $this->validator = $validator;
        return $this;
    }

    public function validateValue(mixed $value, array $allArguments): mixed
    {
        if (!$this->validator) {
            return $value;
        }

        return ($this->validator)($value, $allArguments);
    }

    final public function toGraphQlArgument(TypeRepository $repository): array {
        return [
            'name' => $this->name,
            'type' => $this->type instanceof Type
                ? $this->type
                : ($this->type)($repository),
            'defaultValue' => $this->defaultValue,
            'description' => $this->description,
        ];
    }

}