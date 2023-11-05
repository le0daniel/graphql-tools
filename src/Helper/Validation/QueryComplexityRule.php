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
use GraphQlTools\Contract\ProvidesResultExtension;
use RuntimeException;

class QueryComplexityRule extends QueryComplexity implements ProvidesResultExtension
{
    protected ?int $queryComplexity = null;

    public function isVisibleInResult($context): bool
    {
        return true;
    }

    public function key(): string
    {
        return 'complexity';
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

                        $this->queryComplexity = $this->fieldComplexity($operationDefinition->selectionSet);

                        if ($this->queryComplexity <= $this->maxQueryComplexity) {
                            return;
                        }

                        $this->handleComplexityExceedingAvailableComplexity(
                            $this->queryComplexity,
                            $this->maxQueryComplexity,
                        );
                    },
                ],
            ]
        );
    }

    protected function handleComplexityExceedingAvailableComplexity(int $queryComplexity, int $maxComplexity): void {
        $this->context->reportError(
            new Error(static::maxQueryComplexityErrorMessage(
                $maxComplexity,
                $queryComplexity
            ))
        );
    }

    /**
     * @return int
     */
    public function getQueryComplexity(): int
    {
        if (!isset($this->queryComplexity)) {
            throw new RuntimeException("Tried to query actual complexity before it was set.");
        }

        return $this->queryComplexity;
    }

    public function jsonSerialize(): array
    {
        return [
            'max' => $this->maxQueryComplexity,
            'current' => $this->queryComplexity,
        ];
    }
}