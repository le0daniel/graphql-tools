<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\ValidationRule\RequiresVariableValues;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Utility\Debugging;

final class ValidationRules
{
    private static array $defaultRules;

    /**
     * Set the default rules
     * @param array $defaultRules
     * @return void
     */
    public static function setDefaultRules(array $defaultRules): void {
        self::$defaultRules = $defaultRules;
    }

    public static function getDefaultRules(): array {
        return self::$defaultRules ??= DocumentValidator::defaultRules();
    }

    public function __construct(private readonly array $rules = [])
    {
    }

    public function toArray(): array
    {
        return $this->rules;
    }

    public function get(string $ruleName): ?ValidationRule
    {
        return $this->rules[$ruleName] ?? null;
    }

    public static function initialize(GraphQlContext $context, array $rules, ?array $variableValues): self
    {
        /** @var array<string, ValidationRule> $initializedRules */
        $initializedRules = self::getDefaultRules();

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

        foreach ($initializedRules as $rule) {
            if ($rule instanceof QueryComplexity) {
                $rule->setRawVariableValues($variableValues);
            }
        }

        return new self($initializedRules);
    }

}