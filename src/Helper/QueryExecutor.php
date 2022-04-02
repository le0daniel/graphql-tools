<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQlTools\Context;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Helper\Extension\FieldMessages;

final class QueryExecutor
{
    /** @var callable|null */
    private $errorFormatter;

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterwards. They should be used to collect data and add them to
     * result as an array.
     *
     * @param Schema $schema
     * @param string[]|callable[] $extensionFactories
     * @param array|null $validationRules
     * @param callable|null $errorFormatter
     */
    public function __construct(
        private   readonly Schema $schema,
        private   readonly array $extensionFactories = [FieldMessages::class],
        private   readonly ?array $validationRules = null,
        ?callable $errorFormatter = null
    )
    {
        $this->errorFormatter = $errorFormatter;
    }

    public function execute(
        string  $query,
        Context $context,
        ?array  $variables = null,
        mixed   $rootValue = null,
        ?string $operationName = null,
    ): ExecutionResult
    {
        $extensions = Extensions::createFromExtensionFactories($this->extensionFactories);
        $extensions->dispatchStartEvent(StartEvent::create($query));

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            $result = new ExecutionResult(null, [$exception]);
            $extensions->dispatchEndEvent(EndEvent::create($result));

            $result->extensions = $extensions->jsonSerialize();
            return $result;
        }

        $result = GraphQL::executeQuery(
            $this->schema,
            $source,
            $rootValue,
            contextValue: new OperationContext($context, $extensions),
            variableValues: $variables ?? [],
            operationName: $operationName,
            validationRules: $this->validationRules
        );

        $extensions->dispatchEndEvent(EndEvent::create($result));

        if ($this->errorFormatter) {
            $result->setErrorFormatter($this->errorFormatter);
        }

        $result->extensions = $extensions->jsonSerialize();
        return $result;
    }

}
