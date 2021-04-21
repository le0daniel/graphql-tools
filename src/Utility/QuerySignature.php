<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Visitor;

final class QuerySignature
{
    private const DEFAULT_PIPELINE = [
        [self::class, 'hideLiterals'],
        [self::class, 'removeAliases'],
        [self::class, 'sortAst'],
    ];


    public static function from(string $query, array $pipeline = self::DEFAULT_PIPELINE): string
    {
        $ast = array_reduce(
            $pipeline,
            static fn(DocumentNode $ast, callable $resolver): DocumentNode => $resolver($ast),
            Parser::parse($query)
        );

        return self::printWithReducedWhitespace($ast);
    }

    private static function sortAst(DocumentNode $ast): DocumentNode
    {
        // TODO: Use proper sorting similar to
        // https://github.com/apollographql/apollo-tooling/blob/be117f3140247b71b792a6b52b2ed26f2f76da02/packages/apollo-graphql/src/transforms.ts#L87
        return $ast;
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
            NodeKind::INT => static function(IntValueNode $node): IntValueNode {
                $clone = clone $node;
                $clone->value = '0';
                return $clone;
            },

            NodeKind::FLOAT => static function(FloatValueNode $node) {
                $clone = clone $node;
                $clone->value = '0';
                return $clone;
            },

            NodeKind::STRING => function(StringValueNode $node): StringValueNode {
                $clone = clone $node;
                $clone->value = '';
                return $clone;
            },

            NodeKind::LST => function(ListValueNode $node): ListValueNode {
                $clone = clone $node;
                $clone->values = [];
                return $clone;
            },

            NodeKind::OBJECT => function(ObjectValueNode $node): ObjectValueNode {
                $clone = clone $node;
                $clone->fields = [];
                return $clone;
            },
        ]);
    }

    private static function removeAliases(DocumentNode $ast): DocumentNode
    {
        return Visitor::visit($ast, [
            NodeKind::FIELD => static function(FieldNode $node): FieldNode {
                $clone = clone $node;
                $clone->alias = null;
                return $clone;
            },
        ]);
    }

}
