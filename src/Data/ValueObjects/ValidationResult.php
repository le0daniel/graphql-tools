<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

use GraphQL\Error\Error;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Helper\ValidationRules;

final class ValidationResult
{

    /**
     * @param array<int, Error> $errors
     * @param ValidationRule $validationRules
     */
    public function __construct(
        public readonly array $errors,
        public readonly ValidationRules $validationRules
    )
    {

    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function hasRule(string $name): bool {
        return !!$this->validationRules->get($name);
    }

    public function getRule(string $name): ?ValidationRule {
        return $this->validationRules->get($name);
    }

}