<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Resolver\ProxyResolver;

final class Resolving {

    /** @return callable(): \GraphQL\Type\Definition\Type|\GraphQL\Type\Definition\Type|array  */
    public static function attachProxy($field): mixed {

        // If no resolve function is added, we assume the default resolver is used which means
        // we do not attach the proxy resolver it this case.
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
