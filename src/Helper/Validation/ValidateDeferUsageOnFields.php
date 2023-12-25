<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Error\Error;
use GraphQL\Executor\Values;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQL\Validator\ValidationContext;
use GraphQlTools\Contract\ValidationRule\RequiresVariableValues;

final class ValidateDeferUsageOnFields extends ValidationRule implements RequiresVariableValues
{
    private const DEFER_DIRECTIVE_NAME = 'defer';

    private int $usageCount;
    private ?array $variableValues;

    public function __construct(
        private readonly int $maxAmountOfDeferDirectivesPerQuery = 10
    )
    {
    }

    private function verifySelectionSetNullability(HasFieldsType $parentType, SelectionSetNode $selectionSetNode, ValidationContext $context): void {
        foreach ($selectionSetNode->selections as $node) {
            assert($node instanceof FieldNode);
            $this->verifyOutputTypeIsNullable(
                $parentType->getField($node->name->value),
                $context
            );
        }
    }

    /**
     * @param QueryValidationContext $context
     * @return \Closure[]
     */
    public function getVisitor(ValidationContext $context): array
    {
        $this->usageCount = 0;

        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                if (!$this->nodeUsesDeferDirective($node, $context)) {
                    return;
                }


                $this->usageCount++;
                $this->verifyUsageCount($context);
                $field = $context->getFieldDef();
                assert($field instanceof FieldDefinition);

                $this->verifyOutputTypeIsNullable($field, $context);

                $parentType = $context->getParentType();
                if ($parentType === $context->getSchema()->getMutationType()) {
                    $context->reportError(
                        new Error("The @defer directive can not be used on mutations.")
                    );
                }
            },

            NodeKind::FRAGMENT_SPREAD => function (FragmentSpreadNode $node) use ($context): void {
                if (!$this->nodeUsesDeferDirective($node, $context)) {
                    return;
                }

                $fragment = $context->getFragment($node->name->value);
                assert($fragment instanceof FragmentDefinitionNode);

                $this->usageCount += count($fragment->selectionSet->selections);
                $this->verifyUsageCount($context);

                $this->verifySelectionSetNullability(
                    $context->getParentType(),
                    $fragment->selectionSet,
                    $context
                );
            },

            // In case we encounter an Inline Fragment we spread it out and add it to each of the selected field nodes.
            NodeKind::INLINE_FRAGMENT => function (InlineFragmentNode $node) use ($context): void {
                if (!$this->nodeUsesDeferDirective($node, $context)) {
                    return;
                }

                $this->usageCount += count($node->selectionSet->selections);
                $this->verifyUsageCount($context);

                $this->verifySelectionSetNullability(
                    $context->getParentType(),
                    $node->selectionSet,
                    $context
                );
            },
        ];
    }

    private function verifyUsageCount(ValidationContext $context): void {
        if ($this->usageCount > $this->maxAmountOfDeferDirectivesPerQuery) {
            $context->reportError(
                new Error("The @defer directive can be used at max {$this->maxAmountOfDeferDirectivesPerQuery} times in a query.")
            );
        }
    }

    private function verifyOutputTypeIsNullable(FieldDefinition $field, ValidationContext $context): void
    {
        $output = $field->getType();
        if (!$output instanceof NonNull) {
            return;
        }

        $context->reportError(
            new Error("The @defer directive can only be applied to fields that are nullable. It can not be applied on '{$context->getParentType()->name}.{$field->name}'")
        );
    }

    private function nodeUsesDeferDirective(FieldNode|InlineFragmentNode|FragmentSpreadNode $node, ValidationContext $context): bool
    {
        /** @var DirectiveNode $directive */
        foreach ($node->directives as $directive) {
            if ($directive->name->value === self::DEFER_DIRECTIVE_NAME) {
                $arguments = Values::getDirectiveValues(
                    $context->getSchema()->getDirective(self::DEFER_DIRECTIVE_NAME),
                    $node,
                    $this->variableValues
                );

                return $arguments['if'] ?? true;
            }
        }
        return false;
    }

    public function setVariableValues(?array $values): void
    {
        $this->variableValues = $values;
    }
}