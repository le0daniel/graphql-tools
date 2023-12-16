<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use Generator;
use GraphQL\Error\Error as GraphQlError;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\ValidatedEvent;
use GraphQlTools\Data\ValueObjects\ValidationResult;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Helper\Cache\ExecutionCache;
use GraphQlTools\Helper\Cache\NoCache;
use GraphQlTools\Helper\Extension\Extension;
use GraphQlTools\Helper\Results\CompleteResult;
use GraphQlTools\Helper\Results\PartialBatch;
use GraphQlTools\Helper\Results\PartialResult;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Debugging;
use GraphQlTools\Utility\Errors;
use JsonException;
use RuntimeException;
use Throwable;

class QueryExecutor
{
    public const DEFAULT_MAX_DEFERRED_RUNS = 5;

    public const DEFAULT_VALIDATION_RULE = [
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
        private readonly array    $validationRules = self::DEFAULT_VALIDATION_RULE,
        private readonly ?Closure $errorLogger = null,
        private readonly ?Closure $errorMapper = null,
    )
    {
    }

    /**
     * Used to validate a query without running it. This is done be default when using execute.
     *
     * @param Schema $schema
     * @param string|DocumentNode $query
     * @param GraphQlContext|null $context
     * @param array $variables
     * @return ValidationResult
     * @throws DefinitionException
     * @throws JsonException
     */
    public function validateQuery(
        Schema              $schema,
        string|DocumentNode $query,
        ?GraphQlContext     $context = null,
        array               $variables = [],
    ): ValidationResult
    {
        $context ??= new class implements GraphQlContext {
        };
        $validationRules = ValidationRules::initialize($context, $this->validationRules, $variables);

        try {
            $source = $query instanceof DocumentNode ? $query : Parser::parse($query);
            $validationErrors = DocumentValidator::validate($schema, $source, $validationRules->toArray());
        } catch (SyntaxError $exception) {
            $validationErrors = [$exception];
        }

        return new ValidationResult($validationErrors, $validationRules);
    }

    /**
     * @param Schema $schema
     * @param string|DocumentNode $query
     * @param GraphQlContext $context
     * @param array|null $variables
     * @param mixed|null $rootValue
     * @param string|null $operationName
     * @param int $maxRuns
     * @return Generator<CompleteResult|PartialResult|PartialBatch>
     * @throws DefinitionException
     * @throws JsonException
     * @throws \Exception
     */
    public function executeGenerator(
        Schema              $schema,
        string|DocumentNode $query,
        GraphQlContext      $context = new Context(),
        ?array              $variables = null,
        mixed               $rootValue = null,
        ?string             $operationName = null,
        int                 $maxRuns = self::DEFAULT_MAX_DEFERRED_RUNS,
    ): Generator
    {
        $executor = new ExecutionManager($maxRuns);
        $validationRules = ValidationRules::initialize($context, $this->validationRules, $variables);
        $extensions = Extensions::createFromExtensionFactories($context, $this->extensionFactories);
        $operationContext = new OperationContext(
            $context,
            $extensions,
            $executor,
            $validationRules,
            $maxRuns === 1 ? new NoCache() : new ExecutionCache(),
        );

        $extensions->dispatch(new StartEvent($query, $context, $operationName));

        try {
            $source = $query instanceof DocumentNode ? $query : Parser::parse($query);
        } catch (SyntaxError $exception) {
            $extensions->dispatch(new EndEvent(new ExecutionResult(null, [$exception])));
            yield CompleteResult::withErrorsOnly([$exception], $operationContext);
            return;
        }

        $extensions->dispatch(new ParsedEvent($source, $operationName));
        $errors = DocumentValidator::validate($schema, $source, $validationRules->toArray());
        $extensions->dispatch(new ValidatedEvent($source, $errors));

        if (!empty($errors)) {
            $extensions->dispatch(new EndEvent(new ExecutionResult(null, $errors)));
            yield CompleteResult::withErrorsOnly($errors, $operationContext);;
            return;
        }

        do {
            $executor->start();
            $deferred = $executor->getAllDeferred();
            $executionResult = GraphQL::executeQuery(
                schema: $schema,
                source: $source,
                rootValue: $rootValue,
                contextValue: $operationContext,
                variableValues: $variables ?? [],
                operationName: $operationName,
                fieldResolver: static fn() => throw new RuntimeException("A field was provided that did not include the proxy resolver. This might break extensions and produce unknown side-effects. Did you use the field builder everywhere?"),
                // As we validate the query beforehand, we pass an empty array to avoid double validation.
                validationRules: [],
            );
            $executor->stop();

            if (!$executor->hasDeferred()) {
                // On the last run, extensions should be collected. And dispatch the complete result.
                // All Extensions get the complete result with all data, but without all errors at this time
                $extensions->dispatch(new EndEvent($executionResult));
            } else {
                // We set the result data, so that fields are not resolved twice.
                // If the data is present in the result and not deferred, we directly return the data.
                $operationContext->cache->setResult($executionResult->data);
            }

            // The initial result is different from consecutive results
            // If complete, the first part is a complete result, otherwise
            // it is only partial.
            if ($executor->getCurrentExecution() === 1) {
                yield $this->prepareFirstPart($executionResult, $operationContext);
                continue;
            }

            yield $this->batch($deferred, $executionResult, $operationContext);
        } while ($executor->hasDeferred() && $executor->canExecuteAgain());
    }

    private function batch(array $deferred, ExecutionResult $executionResult, OperationContext $operationContext): PartialResult|PartialBatch
    {
        $batch = [];

        /**
         * @var int $index
         * @var array<string|int> $path
         * @var ?string $label
         */
        foreach ($deferred as $index => [$path, $label]) {
            $hasNext = $operationContext->executor->hasDeferred() || ($index + 1) !== count($deferred);
            $batch[] = PartialResult::part(
                Arrays::getByPathArray($executionResult->data, $path),
                $this->handleErrors(Errors::filterByPath($executionResult->errors, $path)),
                $hasNext,
                $operationContext,
                $label,
                $path
            );
        }

        return count($batch) === 1
            ? $batch[0]
            : new PartialBatch($batch, $operationContext->context, $operationContext->executor->hasDeferred());
    }

    private function prepareFirstPart(
        ExecutionResult  $executionResult,
        OperationContext $operationContext,
    ): CompleteResult|PartialResult
    {
        return $operationContext->executor->hasDeferred()
            ? PartialResult::first(
                $executionResult->data,
                $this->handleErrors($executionResult->errors),
                $operationContext,
            )
            : CompleteResult::from(
                $executionResult->data,
                $this->handleErrors($executionResult->errors),
                $operationContext
            );
    }

    /**
     * @throws DefinitionException
     * @throws JsonException
     */
    public function execute(
        Schema              $schema,
        string|DocumentNode $query,
        GraphQlContext      $context = new Context(),
        ?array              $variables = null,
        mixed               $rootValue = null,
        ?string             $operationName = null,
    ): CompleteResult
    {
        $results = iterator_to_array($this->executeGenerator($schema, $query, $context, $variables, $rootValue, $operationName, 1));

        if (count($results) !== 1) {
            throw new RuntimeException("Something went wrong. Expected 1 result: " . count($results));
        }

        $result = $results[0];
        if (!$result instanceof CompleteResult) {
            throw new RuntimeException('Expected generator to return instance of GraphQlResult, got ' . Debugging::typeOf($result));
        }

        return $result;
    }

    private function mapError(GraphQlError $error): GraphQlError
    {
        if (!$this->errorMapper) {
            return $error;
        }

        $mappedThrowable = ($this->errorMapper)($error->getPrevious());
        return $mappedThrowable === $error->getPrevious()
            ? $error
            : new GraphQlError(
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
