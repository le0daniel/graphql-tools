<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use Exception;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQlTools\Definition\GraphQlScalar;

final class JsonScalar extends GraphQlScalar {

    protected function description(): string {
        return '';
    }

    public function serialize($value) {
        return $value;
    }

    public function parseValue($value): array {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null) {
        if ($valueNode instanceof StringValueNode) {
            return json_decode($valueNode->value, true, 512, JSON_THROW_ON_ERROR);
        }

        if (isset($valueNode->value)) {
            return $valueNode->value;
        }
        throw new Exception("Invalid input type given. Expected string, got {$valueNode->kind}");
    }
}
