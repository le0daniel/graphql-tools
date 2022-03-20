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

    /** @var string[]|callable[] */
    private readonly array $extensions;

    /** @var callable|null */
    private $errorFormatter;

    /**
     * Extensions must be an array of factories or class names which can be constructed
     * without any arguments. Extensions are newly created before the query is executed
     * and destroyed afterwards. They should be used to collect data and add them to
     * result as an array.
     *
     * @param Schema $schema
     * @param array|null $extensionFactories
     * @param array|null $validationRules
     */
    public function __construct(
        private Schema $schema,
        ?array         $extensionFactories = null,
        private readonly ?array $validationRules = null,
        ?callable      $errorFormatter = null
    )
    {
        $this->extensions = $extensionFactories ?? self::defaultExtensions();
        $this->errorFormatter = $errorFormatter;
    }

    public static function defaultExtensions(): array
    {
        return [
            // Tracing::class,
            FieldMessages::class,
        ];
    }

    public function execute(
        string  $query,
        Context $context,
        ?array  $variables = null,
        mixed   $rootValue = null,
        ?string $operationName = null,
    ): ExecutionResult
    {
        $extensions = Extensions::createFromExtensionFactories($this->extensions);
        $extensions->dispatchStartEvent(StartEvent::create($query));

        try {
            $source = Parser::parse($query);
        } catch (SyntaxError $exception) {
            $extensions->dispatchEndEvent(EndEvent::create());
            return new ExecutionResult(null, [$exception], $extensions->jsonSerialize());
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

        $extensions->dispatchEndEvent(EndEvent::create());

        if ($this->errorFormatter) {
            $result->setErrorFormatter($this->errorFormatter);
        }

        $result->extensions = $extensions->jsonSerialize();
        return $result;
    }

}
