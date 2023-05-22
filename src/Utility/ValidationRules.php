<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Definition\DefinitionException;

final class ValidationRules
{

    public static function initialize(array $rules, mixed $context): array {
        $initializedRules = DocumentValidator::defaultRules();

        foreach ($rules as $ruleOrFactory) {
            /** @var ValidationRule $rule */
            $rule = match (true) {
                $ruleOrFactory instanceof ValidationRule => $ruleOrFactory,
                is_string($ruleOrFactory) => new $ruleOrFactory,
                $ruleOrFactory instanceof Closure => $ruleOrFactory($context),
                default => throw new DefinitionException("Expected class-string|Closure|ValidationRule, got: " . gettype($ruleOrFactory)),
            };
            $initializedRules[$rule->getName()] = $rule;
        }

        return $initializedRules;
    }

}