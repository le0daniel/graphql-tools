<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Type\Definition\CompositeType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\ValidationContext;
use GraphQlTools\Contract\ContextualValidationRule;
use GraphQlTools\Data\Models\Message;

class CollectDeprecatedFieldNotices extends ContextualValidationRule
{
    /** @var array<Message>  */
    private array $messages = [];

    public function __construct()
    {
    }

    private static function getParentName(Type|null $parent): string
    {
        return $parent ? $parent->name : '';
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
                $argument = $context->getArgument();
                if (!$argument) {
                    return;
                }

                /** @var string|null $deprecationReason */
                $deprecationReason = $argument->config['deprecatedReason'] ?? null;
                if (!$deprecationReason) {
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

    public function key(): string
    {
        return 'deprecations';
    }

    public function isVisibleInResult(): bool
    {
        return empty($this->messages);
    }

    /**
     * @return Message[]
     */
    public function jsonSerialize(): array
    {
        return $this->messages;
    }
}