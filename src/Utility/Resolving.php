<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Resolver\ProxyResolver;

final class Resolving {

    /** @return callable(): \GraphQL\Type\Definition\Type|\GraphQL\Type\Definition\Type|array  */
    public static function attachProxy($field): mixed {
        if (!is_array($field)){
            return $field;
        }

        // Attach the proxy resolver to all fields which have a defined resolver.
        // This wraps the defined resolver by the ProxyResolver
        if (isset($field['resolve']) && !$field['resolve'] instanceof ProxyResolver){
            $field['resolve'] = new ProxyResolver($field['resolve']);
        }
        return $field;
    }

}
