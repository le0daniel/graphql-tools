<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Error\DebugFlag;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQL\Validator\ValidationContext;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Data\ValueObjects\Deprecations\DeprecatedArgument;
use GraphQlTools\Data\ValueObjects\Deprecations\DeprecatedEnumValue;
use GraphQlTools\Data\ValueObjects\Deprecations\DeprecatedField;

class CollectDeprecatedFieldNotices extends ValidationRule implements ProvidesResultExtension
{
    /** @var array<DeprecatedArgument|DeprecatedField|DeprecatedEnumValue>  */
    private array $messages = [];

    public function __construct()
    {
    }

    private static function getParentName(Type|null $parent): string
    {
        return $parent ? $parent->name : '';
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function getVisitor(ValidationContext $context): array
    {
        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                /** @var FieldDefinition|null $field */
                $field = $context->getFieldDef();
                if (null === $field) {
                    return;
                }

                if ($field->isDeprecated()) {
                    $this->messages[] = new DeprecatedField(
                        $field->name,
                        $context->getParentType()->name,
                        $field->deprecationReason ?? '-- No specific Reason Provided --',
                        $field->config['removalDate'] ?? null,
                    );
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
                    $this->messages[] = new DeprecatedEnumValue(
                        $enum->name,
                        $value->name,
                        $value->deprecationReason ?? '-- No specific Reason Provided --',
                        $value->config['removalDate'] ?? null,
                    );
                }
            },
            NodeKind::ARGUMENT => function (ArgumentNode $node) use ($context) {
                /** @var Argument|null $argument */
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
                $this->messages[] = new DeprecatedArgument(
                    $field->name,
                    $parentName,
                    $argument->name,
                    $deprecationReason,
                    $argument->config['removalDate'] ?? null
                );
            },
        ];
    }

    public function key(): string
    {
        return 'deprecations';
    }

    public function isVisibleInResult($context): bool
    {
        return !empty($this->messages);
    }

    public function serialize(int $debug = DebugFlag::NONE): array
    {
        return $this->messages;
    }
}