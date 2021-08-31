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

final class QueryExecutor {

    private array $extensions;

    /**
     *
     *
     * @param Schema $schema
     * @param array|null $extensions
     * @param array $validationRules
     */
    public function __construct(
        private Schema $schema,
        ?array $extensions = null,
        private array $validationRules = []
    ){
        $this->extensions = $extensions ?? self::defaultExtensions();
    }

    public static function defaultExtensions(): array{
        return [
            Tracing::class,
            FieldMessages::class,
        ];
    }

    public static function withRules(Schema $schema, array $extension = [], ValidationRule...$rules): self{
        return new self(
            $schema, $extension, $rules
        );
    }

    public function execute(
        string $query,
        Context $context,
        ?array $variables = null,
        mixed $rootValue = null,
        ?string $operationName = null,
    ): ExecutionResult {
        $extensionManager = ExtensionManager::create($this->extensions);
        $extensionManager->dispatch(ExtensionManager::START_EVENT, $query);

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

        $extensionManager->dispatch(ExtensionManager::END_EVENT);

        // Append extensions to the result.
        $result->extensions = $extensionManager->jsonSerialize();

        return $result;
    }

}
