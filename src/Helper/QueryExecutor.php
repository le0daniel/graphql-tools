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
use GraphQlTools\Contract\ExceptionWithExtensions;
use GraphQlTools\Contract\ExtendsResult;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\Arrays;

final class QueryExecutor
{
    public const DEFAULT_CONTEXTUAL_VALIDATION_RULE = [
        CollectDeprecatedFieldNotices::class
    ];

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
     * @param array<ValidationRule|Closure|string> $validationRules
     * @param ?Closure $errorLogger
     */
    public function __construct(
        private readonly array $extensionFactories = [],
        private readonly array $validationRules = self::DEFAULT_CONTEXTUAL_VALIDATION_RULE,
        private readonly ?Closure $errorLogger = null,
    )
    {}

    private function initializeValidationRules(GraphQlContext $context): array {
        $rules = DocumentValidator::defaultRules();
        foreach ($this->validationRules as $ruleOrFactory) {
            /** @var ValidationRule $rule */
            $rule = match (true) {
                $ruleOrFactory instanceof ValidationRule => $ruleOrFactory,
                is_string($ruleOrFactory) => new $ruleOrFactory,
                $ruleOrFactory instanceof Closure => $ruleOrFactory($context),
            };
            $rules[$rule->getName()] = $rule;
        }

        return $rules;
    }

    private function collectValidationRuleExtensions(array $validationRules, GraphQlContext $context): array
    {
        $serialized = [];
        foreach ($validationRules as $validationRule) {
            if (!$validationRule instanceof ExtendsResult || !$validationRule->isVisibleInResult($context)) {
                continue;
            }

            $serialized[$validationRule->key()] = $validationRule;
        }
        return $serialized;
    }

    public function execute(
        Schema  $schema,
        string  $query,
        GraphQlContext $context,
        ?array  $variables = null,
        mixed   $rootValue = null,
        ?string $operationName = null,
    ): ExecutionResult
    {
        $extensionManager = ExtensionManager::createFromExtensionFactories($this->extensionFactories);
        $extensionManager->dispatchStartEvent(StartEvent::create($query, $context));
        $validationRules = $this->initializeValidationRules($context);

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            $result = new ExecutionResult(null, [$exception]);
            $extensionManager->dispatchEndEvent(EndEvent::create($result));
            $result->extensions = Arrays::mergeKeyValues(
                $extensionManager->collect($context),
                $this->collectValidationRuleExtensions($validationRules, $context),
                throwOnKeyConflict: true
            );
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
            $extensionManager->collect($context),
            $this->collectValidationRuleExtensions($validationRules, $context),
            throwOnKeyConflict: true
        );

        $result->setErrorFormatter(fn(GraphQlError $error) => $this->formatErrorsWithExtensions($error, $context));
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

    private function formatErrorsWithExtensions(GraphQlError $error, GraphQlContext $context): array {
        $formatted = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        if (!$previous instanceof ExceptionWithExtensions && !$previous instanceof ExtendsResult) {
            return $formatted;
        }

        $previousExtensions = $formatted['extensions'] ?? [];

        if ($previous instanceof ExceptionWithExtensions) {
            $formatted['extensions'] = $previous->getExtensions() + $previousExtensions;
            return $formatted;
        }

        if (!$previous->isVisibleInResult($context)) {
            return $formatted;
        }

        $formatted['extensions'] = [$previous->key() => $previous->jsonSerialize()] + $previousExtensions;
        return $formatted;
    }

}
