<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Error\Error as GraphQlError;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\ValidationResult;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Helper\Extension\Extension;
use GraphQlTools\Helper\Results\GraphQlResult;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\ValidationRules;
use RuntimeException;
use Throwable;

class QueryExecutor
{
    public const DEFAULT_CONTEXTUAL_VALIDATION_RULE = [
        CollectDeprecatedFieldNotices::class
    ];

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterward. They should be used to collect data and add them to
     * result as an array.
     *
     * The error logger receives the original Throwable from the error and is tasked to log it
     * Signature: fn(Throwable $exception, Error $graphQlError): void
     *
     * @param array<class-string<Extension>|Closure(): Extension> $extensionFactories
     * @param array<ValidationRule|Closure(): ValidationRule|class-string<ValidationRule>> $validationRules
     * @param ?Closure(Throwable, GraphQlError): void $errorLogger
     * @param ?Closure(Throwable): Throwable $errorMapper
     */
    public function __construct(
        private readonly array    $extensionFactories = [],
        private readonly array    $validationRules = self::DEFAULT_CONTEXTUAL_VALIDATION_RULE,
        private readonly ?Closure $errorLogger = null,
        private readonly ?Closure $errorMapper = null,
    )
    {
    }

    /**
     * Used to validate a query without running it. This is done be default when using execute.
     *
     * @param Schema $schema
     * @param string $query
     * @param GraphQlContext $context
     * @return ValidationResult
     * @throws DefinitionException
     * @throws SyntaxError
     * @throws \JsonException
     */
    public function validateQuery(
        Schema         $schema,
        string         $query,
        GraphQlContext $context = new Context(),
    ): ValidationResult
    {
        $source = Parser::parse($query);
        $validationRules = ValidationRules::initialize($context, $this->validationRules);
        $validationErrors = DocumentValidator::validate($schema, $source, $validationRules);
        return new ValidationResult($validationErrors, $validationRules);
    }

    public function execute(
        Schema         $schema,
        string         $query,
        GraphQlContext $context = new Context(),
        ?array         $variables = null,
        mixed          $rootValue = null,
        ?string        $operationName = null,
    ): GraphQlResult
    {
        $extensions = Extensions::createFromExtensionFactories($context, $this->extensionFactories);
        $validationRules = ValidationRules::initialize($context, $this->validationRules);
        $extensions->dispatch(StartEvent::create($query, $context, $operationName));

        try {
            $source = Parser::parse($query);
            $extensions->dispatch(ParsedEvent::create($query, $operationName));
            $executionResult = GraphQL::executeQuery(
                schema: $schema,
                source: $source,
                rootValue: $rootValue,
                contextValue: new OperationContext($context, $extensions),
                variableValues: $variables ?? [],
                operationName: $operationName,
                fieldResolver: static fn() => throw new RuntimeException("A field was provided that did not include the proxy resolver. This might break extensions and produce unknown side-effects. Did you use the field builder everywhere?"),
                validationRules: $validationRules,
            );

            // We only use the error handler and mapper for errors that occur during execution.
            $executionResult->errors = $this->handleErrors($executionResult->errors);
        } catch (SyntaxError $exception) {
            $executionResult = new ExecutionResult(null, [$exception]);
        }

        $extensions->dispatch(EndEvent::create($executionResult));

        return GraphQlResult::fromExecutionResult(
            $executionResult,
            $context,
            $validationRules,
            $extensions->getKeyedExtensions(),
        );
    }

    private function mapError(GraphQlError $error): GraphQlError
    {
        if (!$this->errorMapper) {
            return $error;
        }

        $mappedThrowable = ($this->errorMapper)($error->getPrevious());
        return new GraphQlError(
            $mappedThrowable->getMessage(),
            $error->nodes,
            $error->getSource(),
            $error->getPositions(),
            $error->getPath(),
            $mappedThrowable
        );
    }

    private function logError(GraphQlError $error): void
    {
        if ($this->errorLogger) {
            ($this->errorLogger)($error->getPrevious(), $error);
        }
    }

    /**
     * @param GraphQlError[] $errors
     * @return array
     */
    private function handleErrors(array $errors): array
    {
        return array_map(function (GraphQlError $graphQlError): GraphQlError {
            if (!$graphQlError->getPrevious()) {
                return $graphQlError;
            }

            $this->logError($graphQlError);
            return $this->mapError($graphQlError);
        }, $errors);
    }


}
