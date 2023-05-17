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

    public function hasRule(string $name): bool {
        return array_key_exists($name, $this->validationRules);
    }

    /**
     * @template T of ValidationRule
     * @param class-string<ValidationRule> $name
     * @return T|null
     */
    public function getRule(string $name): ?ValidationRule {
        return $this->validationRules[$name] ?? null;
    }

}