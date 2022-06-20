<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Context;
use GraphQlTools\Contract\ContextualValidationRule;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Helper\Extension\Tracing;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\Arrays;

final class QueryExecutor
{
    public const DEFAULT_CONTEXTUAL_VALIDATION_RULE = [CollectDeprecatedFieldNotices::class];
    public const DEFAULT_ENABLED_EXTENSIONS = [Tracing::class];

    /** @var ValidationRule[] */
    private readonly array $validationRules;

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterwards. They should be used to collect data and add them to
     * result as an array.
     *
     * @param Schema $schema
     * @param string[]|callable[] $extensionFactories
     * @param ValidationRule[] $validationRules
     */
    public function __construct(
        private readonly Schema $schema,
        private readonly array  $extensionFactories = self::DEFAULT_ENABLED_EXTENSIONS,
        array                   $validationRules = [],
    )
    {
        $this->validationRules = empty($validationRules)
            ? DocumentValidator::allRules()
            : $validationRules;
    }

    private function initializeContextualValidationRules(array $validationRules): array
    {
        return Arrays::mapWithKeys($validationRules, static function ($key, Closure|string|ValidationRule $factory): array {
            if ($factory instanceof ValidationRule) {
                return [$factory->getName(), $factory];
            }

            /** @var ValidationRule $instance */
            $instance = is_string($factory)
                ? new $factory
                : $factory();

            if (!$instance instanceof ValidationRule) {
                throw DefinitionException::from($instance, ValidationRule::class);
            }

            return [$instance->getName(), $instance];
        });
    }

    private function serializeValidationRules(array $validationRules): array
    {
        $serialized = [];
        foreach ($validationRules as $validationRule) {
            if (!$validationRule instanceof ContextualValidationRule || !$validationRule->isVisibleInResult()) {
                continue;
            }

            $serialized[$validationRule->key()] = $validationRule;
        }
        return $serialized;
    }

    public function execute(
        string  $query,
        Context $context,
        ?array  $variables = null,
        mixed   $rootValue = null,
        ?string $operationName = null,
        array   $contextualValidationRules = self::DEFAULT_CONTEXTUAL_VALIDATION_RULE,
    ): ExecutionResult
    {
        $extensionManager = ExtensionManager::createFromExtensionFactories($this->extensionFactories);
        $extensionManager->dispatchStartEvent(StartEvent::create($query));


        $validationRules = [
            ... $this->validationRules,
            ... $this->initializeContextualValidationRules($contextualValidationRules),
        ];

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            $result = new ExecutionResult(null, [$exception]);
            $extensionManager->dispatchEndEvent(EndEvent::create($result));

            $result->extensions = $extensionManager->jsonSerialize();
            return $result;
        }

        $result = GraphQL::executeQuery(
            schema: $this->schema,
            source: $source,
            rootValue: $rootValue,
            contextValue: new OperationContext($context, $extensionManager),
            variableValues: $variables ?? [],
            operationName: $operationName,
            validationRules: $validationRules,
        );

        $extensionManager->dispatchEndEvent(EndEvent::create($result));

        $result->extensions = Arrays::mergeKeyValues(
            $extensionManager->jsonSerialize(),
            $this->serializeValidationRules($validationRules),
            throwOnKeyConflict: true
        );

        return $result;
    }

}
