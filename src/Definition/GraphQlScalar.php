<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use Closure;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ScalarType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;

abstract class GraphQlScalar extends TypeDefinition implements DefinesGraphQlType, LeafType
{
    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): ScalarType
    {
        $config = [
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
        ];

        return new class ($config, $this->serialize(...), $this->parseValue(...), $this->parseLiteral(...)) extends ScalarType {

            public function __construct(
                array                    $config,
                private readonly Closure $serialize,
                private readonly Closure $parse,
                private readonly Closure $parseLiteral,
            )
            {
                parent::__construct($config);
            }

            public function serialize($value)
            {
                return ($this->serialize)($value);
            }

            public function parseValue($value)
            {
                return ($this->parse)($value);
            }

            public function parseLiteral(Node $valueNode, array $variables = null)
            {
                return ($this->parseLiteral)($valueNode, $variables);
            }
        };
    }
}
