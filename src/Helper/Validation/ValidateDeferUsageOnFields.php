<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Error\Error;
use GraphQL\Executor\Values;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\NonNull;
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

    public function getVisitor(ValidationContext $context): array
    {
        $this->usageCount = 0;

        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                if (!$this->fieldUsesDeferDirective($node, $context)) {
                    return;
                }

                $this->usageCount++;
                $field = $context->getFieldDef();
                assert($field instanceof FieldDefinition);

                if ($this->usageCount > $this->maxAmountOfDeferDirectivesPerQuery) {
                    $context->reportError(
                        new Error("The @defer directive can be used at max {$this->maxAmountOfDeferDirectivesPerQuery} times in a query.")
                    );
                }

                $this->verifyOutputTypeIsNullable($field, $context);

                $parentType = $context->getParentType();
                if ($parentType === $context->getSchema()->getMutationType()) {
                    $context->reportError(
                        new Error("The @defer directive can not be used on mutations.")
                    );
                }
            },
        ];
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

    private function fieldUsesDeferDirective(FieldNode $node, ValidationContext $context): bool
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