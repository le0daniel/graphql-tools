<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

final class Argument extends InputField
{
    /** @var null|callable  */
    private $validator = null;

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
    final public function validateValue(mixed $value, array $allArguments): mixed
    {
        if (!$this->validator) {
            return $value;
        }

        return ($this->validator)($value, $allArguments);
    }

}