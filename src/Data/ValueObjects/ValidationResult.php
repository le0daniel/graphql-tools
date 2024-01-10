<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

use GraphQL\Error\Error;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Helper\ValidationRules;

final readonly class ValidationResult
{

    /**
     * @param array<int, Error> $errors
     * @param ValidationRules $validationRules
     */
    public function __construct(
        public array           $errors,
        public ValidationRules $validationRules
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