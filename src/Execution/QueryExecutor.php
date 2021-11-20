<?php

declare(strict_types=1);

namespace GraphQlTools\Execution;

use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Context;
use GraphQlTools\Extension\FieldMessages;
use GraphQlTools\Extension\Tracing;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\Utility\SideEffects;

final class QueryExecutor {

    /** @var string[]|callable[]  */
    private array $extensions;

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterwards. They should be used to collect data and add them to
     * result as an array.
     *
     * @param Schema $schema
     * @param array|null $extensions
     * @param array|null $validationRules
     */
    public function __construct(
        private Schema $schema,
        ?array $extensions = null,
        private ?array $validationRules = null
    ){
        $this->extensions = $extensions ?? self::defaultExtensions();
    }

    public static function defaultExtensions(): array{
        return [
            Tracing::class,
            FieldMessages::class,
        ];
    }

    public function execute(
        string $query,
        Context $context,
        ?array $variables = null,
        mixed $rootValue = null,
        ?string $operationName = null,
    ): ExecutionResult {
        $extensionManager = Extensions::create($this->extensions);
        $extensionManager->dispatch(Extensions::START_EVENT, $query);

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            return new ExecutionResult(null, [$exception]);
        }

        $result = GraphQL::executeQuery(
            $this->schema,
            $source,
            $rootValue,
            new OperationContext($context, $extensionManager),
            $variables ?? [],
            $operationName,
            [ProxyResolver::class, 'default'],
            $this->validationRules
        );

        $extensionManager->dispatch(Extensions::END_EVENT);

        // Append extensions to the result.
        $result->extensions = $extensionManager->jsonSerialize();
        return $result;
    }

}
