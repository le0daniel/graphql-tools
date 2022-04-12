<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Type\Definition\CompositeType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQL\Validator\ValidationContext;
use GraphQlTools\Data\Models\Message;
use GraphQlTools\Utility\Fields;

class CollectFieldMessagesValidation extends ValidationRule
{
    private array $messages = [];

    public function __construct()
    {
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    private static function getParentName(CompositeType|null $parent): string
    {
        return $parent ? $parent->name : '';
    }

    private function inspectObjectInputForDeprecations(ObjectValueNode $node, ValidationContext $context): void
    {
        /** @var InputObjectType $type */
        $type = $context->getInputType();

        foreach ($node->fields as $inputField) {
            $field = $type->getField($inputField->name->value);

            $deprecationReason = $field->config['deprecatedReason'] ?? false;
            $isDeprecated = !!$deprecationReason;

            if ($isDeprecated) {
                var_dump("Deprecated Input field used: {$type->name}.{$field->name}");
            }
        }
    }

    public function getVisitor(ValidationContext $context)
    {
        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                /** @var FieldDefinition|null $field */
                $field = $context->getFieldDef();
                if (null === $field) {
                    return;
                }

                /** @var ObjectType|InterfaceType $parentType */
                $parentType = $context->getParentType();

                if ($field->isDeprecated()) {
                    $reason = $field->deprecationReason ?? '-- No specific Reason Provided --';
                    $this->messages[] = Message::deprecated($field->name, $parentType, $reason);
                }

                if ($notice = Fields::getFieldNotice($field)) {
                    $this->messages[] = Message::notice($notice);
                }
            },
            NodeKind::ENUM => function (EnumValueNode $node) use ($context): void {
                $enum = $context->getInputType();
                if (!$enum instanceof EnumType) {
                    return;
                }

                $value = $enum->getValue($node->value);
                if (!$value instanceof EnumValueDefinition) {
                    return;
                }

                if ($value->isDeprecated()) {
                    $this->messages[] = Message::deprecated(
                        $enum->name, $value->name, $value->deprecationReason ?? '-- No specific Reason Provided --'
                    );
                }
            },
            NodeKind::ARGUMENT => function (ArgumentNode $node) use ($context) {
                /** @var FieldArgument $argument */
                $argument = $context->getArgument();
                if (!$argument) {
                    return;
                }

                $deprecationReason = $argument->config['deprecatedReason'] ?? false;
                $isDeprecated = !!$deprecationReason;

                // $isObjectInput = $node->value instanceof ObjectValueNode;
                // if ($isObjectInput && $this->recursivelyInspectInputObjectForDeprecations) {
                //     $this->inspectObjectInputForDeprecations($node->value, $context);
                // }

                if (!$isDeprecated) {
                    return;
                }

                $field = $context->getFieldDef();
                $parentName = self::getParentName($context->getParentType());
                $this->messages[] = Message::deprecatedArgument(
                    $field->name,
                    $parentName,
                    $argument->name,
                    $deprecationReason
                );
            },
        ];
    }

}