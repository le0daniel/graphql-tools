<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\GraphQlDirective;
use GraphQlTools\Utility\Types;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

/**
 * @deprecated Please use the SchemaRegistry directly
 */
class FederatedSchema extends SchemaRegistry
{


}