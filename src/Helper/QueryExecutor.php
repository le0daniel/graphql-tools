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
use GraphQlTools\Data\ValueObjects\ValidationResult;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\ValidationRules;

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
     * @param array<ValidationRule|Closure|class-string> $validationRules
     * @param ?Closure $errorLogger
     */
    public function __construct(
        private readonly array $extensionFactories = [],
        private readonly array $validationRules = self::DEFAULT_CONTEXTUAL_VALIDATION_RULE,
        private readonly ?Closure $errorLogger = null,
    )
    {}

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

    /**
     * Used to validate a query without running it. This is done be default when using execute.
     *
     * @param Schema $schema
     * @param string $query
     * @param GraphQlContext|null $context
     * @return ValidationResult
     * @throws DefinitionException
     * @throws SyntaxError
     * @throws \JsonException
     */
    public function validateQuery(
        Schema $schema,
        string $query,
        ?GraphQlContext $context = null,
    ): ValidationResult {
        $context ??= new Context();
        $source = Parser::parse($query);
        $validationRules = ValidationRules::initialize($this->validationRules, $context);
        $validationErrors = DocumentValidator::validate($schema, $source, $validationRules);
        return new ValidationResult($validationErrors, $validationRules);
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
        $validationRules = ValidationRules::initialize($this->validationRules, $context);

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            $result = new ExecutionResult(null, [$exception]);
            $extensionManager->dispatchEndEvent(EndEvent::create($result));
            $result->extensions = $extensionManager->collect($context);
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
        $hasCustomErrorLogger = !!$this->errorLogger;

        foreach ($errors as $graphQlError) {
            $hasPreviousError = !!$graphQlError->getPrevious();
            if ($hasCustomErrorLogger && $hasPreviousError) {
                ($this->errorLogger)($graphQlError->getPrevious(), $graphQlError);
            }

            $formattedErrors[] = $formatter($graphQlError);
        }
        return $formattedErrors;
    }

    private function formatErrorsWithExtensions(GraphQlError $error, GraphQlContext $context): array {
        $formatted = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        if (!$previous instanceof ExceptionWithExtensions) {
            return $formatted;
        }

        $previousExtensions = $formatted['extensions'] ?? [];
        $formatted['extensions'] = $previous->getExtensions() + $previousExtensions;
        return $formatted;
    }

}
