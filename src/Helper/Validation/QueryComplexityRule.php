<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Error\Error;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitorOperation;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQlTools\Contract\ExtendsResult;
use RuntimeException;

class QueryComplexityRule extends QueryComplexity implements ExtendsResult
{
    protected ?int $actualComplexity = null;

    public function isVisibleInResult($context): bool
    {
        return true;
    }

    public function key(): string
    {
        return 'queryComplexity';
    }

    public function getVisitor(QueryValidationContext $context): array
    {
        $this->context = $context;
        $this->variableDefs = new NodeList([]);
        $this->fieldNodeAndDefs = new \ArrayObject();

        return $this->invokeIfNeeded(
            $context,
            [
                NodeKind::SELECTION_SET => function (SelectionSetNode $selectionSet) use ($context): void {
                    $this->fieldNodeAndDefs = $this->collectFieldASTsAndDefs(
                        $context,
                        $context->getParentType(),
                        $selectionSet,
                        null,
                        $this->fieldNodeAndDefs
                    );
                },
                NodeKind::VARIABLE_DEFINITION => function ($def): VisitorOperation {
                    $this->variableDefs[] = $def;

                    return Visitor::skipNode();
                },
                NodeKind::OPERATION_DEFINITION => [
                    'leave' => function (OperationDefinitionNode $operationDefinition) use ($context): void {
                        $errors = $context->getErrors();

                        if ($errors !== []) {
                            return;
                        }

                        $this->actualComplexity = $this->fieldComplexity($operationDefinition->selectionSet);

                        if ($this->actualComplexity <= $this->maxQueryComplexity) {
                            $this->handleConsumedComplexity($this->actualComplexity);
                            return;
                        }

                        $this->handleComplexityExceedingAvailableComplexity(
                            $this->actualComplexity,
                            $this->maxQueryComplexity,
                        );
                    },
                ],
            ]
        );
    }

    public function getName(): string
    {
        return QueryComplexity::class;
    }

    protected function handleConsumedComplexity(int $actualComplexity): void {
        // Use this method to update complexity in a session for example
    }

    protected function handleComplexityExceedingAvailableComplexity(int $actualComplexity, int $maxComplexity): void {
        $this->context->reportError(
            new Error(static::maxQueryComplexityErrorMessage(
                $maxComplexity,
                $actualComplexity
            ))
        );
    }

    /**
     * @return int
     */
    public function getActualComplexity(): int
    {
        if (!isset($this->actualComplexity)) {
            throw new RuntimeException("Tried to query actual complexity before it was set.");
        }

        return $this->actualComplexity;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'allowed' => $this->maxQueryComplexity,
            'actual' => $this->actualComplexity,
        ];
    }
}