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

    public function __construct(
        private Schema $schema,
        private array $extensions = [],
        private array $validationRules = []
    ){
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
        $extensionManager = ExtensionManager::create(
            empty($this->extensions) ? self::defaultExtensions() : $this->extensions
        );
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
