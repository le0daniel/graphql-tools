<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\HasSelectionSet;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\OperationDefinitionNode;

final class AST
{
    private static function getSelections(mixed $node): NodeList|null {
        if ($node instanceof HasSelectionSet) {
            return $node->getSelectionSet()->selections;
        }

        return null;
    }

    public static function getFragmentSpreadPaths(string $path, OperationDefinitionNode $node, array &$fragmentDefinitions = []): array {
        $paths = [];
        $selections = self::getSelections($node);

        foreach ($selections as $selection) {
            if ($selection instanceof FieldNode) {
                $name = $selection->alias?->value ?? $selection->name->value;
            }
        }
    }

    /**
     * @param array $path
     * @param OperationDefinitionNode $node
     * @param array<string, FragmentDefinitionNode> $fragments
     * @return FragmentSpreadNode
     */
    public static function getFragmentSpread(array $path, OperationDefinitionNode $node, array $fragments = []): mixed {
        /** @var NodeList<FieldNode|FragmentSpreadNode> $selections */
        $selections = self::getSelections($node);
        $fragmentSpread = null;

        while ($selections && !empty($path)) {
            $currentPart = array_shift($path);

            // Skip Lists
            if (is_int($currentPart)) {
                continue;
            }

            foreach ($selections as $selection) {
                if ($selection instanceof FieldNode && ($selection->name->value === $currentPart || $selection->alias?->value === $currentPart)) {
                    $fragmentSpread = null;
                    $selections = self::getSelections($selection);
                    break;
                }

                if ($selection instanceof FragmentSpreadNode) {
                    $fragment = $fragments[$selection->name->value];
                    assert($fragment instanceof FragmentDefinitionNode);

                    foreach ($fragment->selectionSet->selections as $fragmentSelection) {
                        assert($fragmentSelection instanceof FieldNode);
                        if ($fragmentSelection->name->value !== $currentPart && $selection->alias?->value !== $currentPart) {
                            continue;
                        }

                        $fragmentSpread = $selection;
                        $selections = self::getSelections($fragment);
                        break;
                    }
                }
            }
        }

        if (!empty($path)) {
            return $path;
        }

        return $fragmentSpread;
    }

}