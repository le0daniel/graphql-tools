<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Error\Error as GraphQlError;
use GraphQL\Error\FormattedError;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ContextualValidationRule;
use GraphQlTools\Contract\ExceptionWithExtensions;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Typing;

final class QueryExecutor
{
    public const DEFAULT_CONTEXTUAL_VALIDATION_RULE = [CollectDeprecatedFieldNotices::class];

    /** @var ValidationRule[] */
    private readonly array $validationRules;

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterwards. They should be used to collect data and add them to
     * result as an array.
     *
     * The error logger receives the original Throwable from the error and is tasked to log it
     * Signature: fn(Throwable $exception, Error $graphQlError): void
     *
     * @param class-string[]|Closure[] $extensionFactories
     * @param ValidationRule[] $validationRules
     * @param ?Closure $errorLogger
     */
    public function __construct(
        private readonly array $extensionFactories = [],
        array                  $validationRules = [],
        private readonly ?Closure $errorLogger = null,
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
            $instance = is_string($factory) ? new $factory : $factory();
            Typing::verifyOfType(ValidationRule::class, $instance);
            return [$instance->getName(), $instance];
        });
    }

    private function collectValidationRuleExtensions(array $validationRules): array
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
        Schema  $schema,
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
            $result->extensions = $extensionManager->collect();
            return $result;
        }

        $result = GraphQL::executeQuery(
            schema: $schema,
            source: $source,
            rootValue: $rootValue,
            contextValue: new OperationContext($context, $extensionManager),
            variableValues: $variables ?? [],
            operationName: $operationName,
            validationRules: $validationRules,
        );

        $extensionManager->dispatchEndEvent(EndEvent::create($result));

        $result->extensions = Arrays::mergeKeyValues(
            $extensionManager->collect(),
            $this->collectValidationRuleExtensions($validationRules),
            throwOnKeyConflict: true
        );

        $result->setErrorFormatter($this->formatErrorsWithExtensions(...));
        $result->setErrorsHandler($this->handleErrors(...));

        return $result;
    }

    /**
     * @param GraphQlError[] $errors
     * @param callable $formatter
     * @return array
     */
    private function handleErrors(array $errors, callable $formatter): array {
        $formattedErrors = [];
        $hasErrorLogger = !!$this->errorLogger;

        foreach ($errors as $graphQlError) {
            // Optionally log the error if configured correctly
            if ($hasErrorLogger && $originalException = $graphQlError->getPrevious()) {
                ($this->errorLogger)($originalException, $graphQlError);
            }

            $formattedErrors[] = $formatter($graphQlError);
        }
        return $formattedErrors;
    }

    private function formatErrorsWithExtensions(GraphQlError $error): array {
        $formatted = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        if (!$previous instanceof ExceptionWithExtensions) {
            return $formatted;
        }

        $previousExtensions = $formatted['extensions'] ?? [];

        // Overwrite the extensions
        $formatted['extensions'] = $previous->getExtensions() + $previousExtensions;
        return $formatted;
    }

}
