<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Language\AST\Node;
use GraphQlTools\Definition\GraphQlScalar;
use RuntimeException;

final class MetadataScalar extends GraphQlScalar
{
    public static function typeName(): string
    {
        return '__CustomMetadata';
    }

    protected function description(): string
    {
        return 'Internal Scalar for custom type and field metadata.';
    }

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        throw new RuntimeException('This is only a return type and can not be parsed');
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        throw new RuntimeException('This is only a return type and can not be parsed');
    }
}