<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

use GraphQL\Error\Error;
use GraphQL\Validator\Rules\ValidationRule;

final class ValidationResult
{

    /**
     * @param array<int, Error> $errors
     * @param array<string, ValidationRule> $validationRules
     */
    public function __construct(
        public readonly array $errors,
        public readonly array $validationRules
    )
    {

    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * @template T of ValidationRule
     * @param class-string<T> $name
     * @return T|null
     */
    public function getRule(string $name): ?ValidationRule {
        return $this->validationRules[$name] ?? null;
    }

}