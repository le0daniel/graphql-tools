<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\ValidationRule\RequiresVariableValues;
use GraphQlTools\Definition\DefinitionException;

final class ValidationRules
{

    /**
     * @internal
     * @param array<ValidationRule|callable-string<ValidationRule>|Closure(): ValidationRule> $rules
     * @return array<string, ValidationRule>
     * @throws DefinitionException
     */
    public static function initialize(GraphQlContext $context, array $rules, ?array $variableValues): array {
        $initializedRules = DocumentValidator::defaultRules();

        foreach ($rules as $ruleOrFactory) {
            /** @var ValidationRule|null $rule */
            $rule = match (true) {
                $ruleOrFactory instanceof ValidationRule => $ruleOrFactory,
                is_string($ruleOrFactory) => new $ruleOrFactory,
                $ruleOrFactory instanceof Closure => $ruleOrFactory($context),
                default => throw new DefinitionException("Expected class-string|Closure|ValidationRule, got: " . Debugging::typeOf($ruleOrFactory)),
            };

            if ($rule instanceof RequiresVariableValues) {
                $rule->setVariableValues($variableValues);
            }

            if ($rule) {
                $initializedRules[$rule->getName()] = $rule;
            }
        }

        return $initializedRules;
    }

}