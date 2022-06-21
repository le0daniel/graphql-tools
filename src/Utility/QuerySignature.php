<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Visitor;

final class QuerySignature
{
    private const INT_LITERAL_VALUE = '0';
    private const FLOAT_LITERAL_VALUE = '0';
    private const STRING_LITERAL_VALUE = '';
    private const LIST_LITERAL_VALUE = [];
    private const OBJECT_LITERAL_VALUE = [];

    private const DEFAULT_PIPELINE = [
        [self::class, 'hideLiterals'],
        [self::class, 'removeAliases'],
        [self::class, 'sortAst'],
    ];


    public static function createSignatureString(string $query, array $pipeline = self::DEFAULT_PIPELINE): string
    {
        $ast = array_reduce(
            $pipeline,
            static fn(DocumentNode $ast, callable $resolver): DocumentNode => $resolver($ast),
            Parser::parse($query)
        );

        return self::printWithReducedWhitespace($ast);
    }

    public static function createHashString(string $query, array $pipeline = self::DEFAULT_PIPELINE): string
    {
        return md5(self::createSignatureString($query, $pipeline));
    }

    /**
     * @param Node[] $nodes
     * @return array
     */
    private static function splitByNodeKind(array $nodes): array
    {
        $splitNodes = [];

        foreach ($nodes as $node) {
            if (!isset($splitNodes[$node->kind])) {
                $splitNodes[$node->kind] = [];
            }
            $splitNodes[$node->kind][] = $node;
        }

        return $splitNodes;
    }

    private static function sortBy(array|\Traversable|null $nodes, string ...$keys): array
    {
        if (!$nodes) {
            return [];
        }

        $nodesArray = is_array($nodes) ? $nodes : iterator_to_array($nodes);
        return array_reduce($keys, static function (array $nodesToSort, string $sortByKey) {
            return Arrays::sortByColumn($nodesToSort, $sortByKey);
        }, $nodesArray);
    }

    private static function sortAst(DocumentNode $ast): DocumentNode
    {
        // Here, the direct object is manipulated as it did not work by cloning the Node.
        // This might change in future versions...
        return Visitor::visit($ast, [
            NodeKind::DOCUMENT => static function (DocumentNode $node): DocumentNode {
                $node->definitions = NodeList::create(self::sortBy($node->definitions, 'kind', 'name.value'));
                return $node;
            },
            NodeKind::OPERATION_DEFINITION => static function (OperationDefinitionNode $node): OperationDefinitionNode {
                $node->variableDefinitions = NodeList::create(self::sortBy($node->variableDefinitions, 'variable.name.value'));
                return $node;
            },
            NodeKind::SELECTION_SET => static function (SelectionSetNode $node) {
                $splitNodeList = self::splitByNodeKind(
                    self::sortBy($node->selections, 'kind', 'name.value')
                );

                // Sort inline fragments by name
                if (array_key_exists(NodeKind::INLINE_FRAGMENT, $splitNodeList)) {
                    $splitNodeList[NodeKind::INLINE_FRAGMENT] = self::sortBy(
                        $splitNodeList[NodeKind::INLINE_FRAGMENT],
                        'typeCondition.name.value'
                    );
                }

                $node->selections = NodeList::create(Arrays::nonRecursiveFlatten($splitNodeList));
                return $node;
            },

            NodeKind::FIELD => static function (FieldNode $node): FieldNode {
                if ($node->arguments->count() === 0) {
                    return $node;
                }

                $node->arguments = NodeList::create(self::sortBy($node->arguments, 'name.value'));
                return $node;
            },

            NodeKind::FRAGMENT_SPREAD => static function (FragmentSpreadNode $node): FragmentSpreadNode {
                $node->directives = NodeList::create(self::sortBy($node->directives, 'name.value'));
                return $node;
            },

            NodeKind::INLINE_FRAGMENT => static function (InlineFragmentNode $node): InlineFragmentNode {
                $node->directives = NodeList::create(self::sortBy($node->directives, 'name.value'));
                return $node;
            },

            NodeKind::FRAGMENT_DEFINITION => static function (FragmentDefinitionNode $node): FragmentDefinitionNode {
                $node->directives = NodeList::create(self::sortBy($node->directives, 'name.value'));
                $node->variableDefinitions = NodeList::create(self::sortBy($node->variableDefinitions, 'variable.name.value'));
                return $node;
            },

            NodeKind::DIRECTIVE => static function (DirectiveNode $node): DirectiveNode {
                $node->arguments = NodeList::create(self::sortBy($node->arguments, 'name.value'));
                return $node;
            }
        ]);
    }

    private static function printWithReducedWhitespace(DocumentNode $ast): string
    {
        $result = Printer::doPrint($ast);
        $noNewLines = str_replace(PHP_EOL, ' ', $result);
        return trim(preg_replace('/\s{2,}/', ' ', $noNewLines));
    }

    private static function hideLiterals(DocumentNode $ast): DocumentNode
    {
        return Visitor::visit($ast, [
            NodeKind::INT => static function (IntValueNode $node): IntValueNode {
                $clone = clone $node;
                $clone->value = self::INT_LITERAL_VALUE;
                return $clone;
            },

            NodeKind::FLOAT => static function (FloatValueNode $node) {
                $clone = clone $node;
                $clone->value = self::FLOAT_LITERAL_VALUE;
                return $clone;
            },

            NodeKind::STRING => function (StringValueNode $node): StringValueNode {
                $clone = clone $node;
                $clone->value = self::STRING_LITERAL_VALUE;
                return $clone;
            },

            NodeKind::LST => function (ListValueNode $node): ListValueNode {
                $clone = clone $node;
                $clone->values = self::LIST_LITERAL_VALUE;
                return $clone;
            },

            NodeKind::OBJECT => function (ObjectValueNode $node): ObjectValueNode {
                $clone = clone $node;
                $clone->fields = self::OBJECT_LITERAL_VALUE;
                return $clone;
            },
        ]);
    }

    private static function removeAliases(DocumentNode $ast): DocumentNode
    {
        return Visitor::visit($ast, [
            NodeKind::FIELD => static function (FieldNode $node): FieldNode {
                $clone = clone $node;
                $clone->alias = null;
                return $clone;
            },
        ]);
    }

}
