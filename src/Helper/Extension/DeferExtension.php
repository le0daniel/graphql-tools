<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Executor\Values;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use GraphQlTools\Contract\Events\VisitField;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\Extension\ListensToLifecycleEvents;
use GraphQlTools\Contract\Extension\ManipulatesAst;
use GraphQlTools\Utility\AST;

class DeferExtension extends Extension implements InteractsWithFieldResolution, ManipulatesAst, ListensToLifecycleEvents
{
    private const DEFER_DIRECTIVE_NAME = 'defer';

    /**
     * Requires a low priority to work correctly. This should be the most important
     * extension.
     * @return int
     */
    public function priority(): int
    {
        return -100;
    }

    public function visitField(VisitField $event): void
    {
        if (!$event->canDefer()) {
            return;
        }

        $fragmentSpread = AST::getFragmentSpread($event->info->path, $event->info->operation, $event->info->fragments);
        if ($fragmentSpread) {
            $this->handleOther($event);
        }

        if (!$event->hasDirective(self::DEFER_DIRECTIVE_NAME)) {
            return;
        }


        $arguments = $event->getDirectiveArguments(self::DEFER_DIRECTIVE_NAME);
        if ($arguments['if'] ?? true) {
            $event->defer($arguments['label'] ?? null);
        }
    }

    private function handleOther(VisitField $event): void {}

    private function directiveNode(FragmentSpreadNode|InlineFragmentNode $node, Schema $schema, ?array $variables): ?DirectiveNode {
        foreach ($node->directives as $directive) {
            if ($directive->name->value === self::DEFER_DIRECTIVE_NAME) {
                $arguments = Values::getDirectiveValues(
                    $schema->getDirective(self::DEFER_DIRECTIVE_NAME),
                    $node,
                    $variables
                );

                // If disabled, we skip this one
                $isEnabled = $arguments['if'] ?? true;
                if ($isEnabled) {
                    return clone $directive;
                }
            }
        }
        return null;
    }

    public function applyDirectiveToSelectionSet(InlineFragmentNode $node, DirectiveNode $directiveNode): InlineFragmentNode {
        /** @var FieldNode|mixed $selectionNode */
        foreach ($node->selectionSet->selections as $selectionNode) {
            if (!$selectionNode instanceof FieldNode) {
                continue;
            }

            // Manipulates the AST and adds the directive to all fields of the selection set
            $selectionNode->directives[] = $directiveNode;
        }

        return $node;
    }

    public function visitor(Schema $schema, ?array $variables, TypeInfo $typeInfo): ?array
    {
        return [
            NodeKind::FRAGMENT_SPREAD => function (FragmentSpreadNode $node) use ($schema, $variables, $typeInfo) {
                $directive = $this->directiveNode($node, $schema, $variables);

               // $typeInfo->
            },
            // We need to expand the defer directive and add it to all fields of the inline fragment
            NodeKind::INLINE_FRAGMENT => function (InlineFragmentNode $node) use ($schema, $variables) {
                $directive = $this->directiveNode($node, $schema, $variables);
                return $directive
                    ? $this->applyDirectiveToSelectionSet($node->cloneDeep(), $directive)
                    : $node;
            },
        ];
    }
}