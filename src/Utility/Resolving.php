<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Resolver\ProxyResolver;

final class Resolving {

    public static function attachProxyToField(FieldDefinition &$field): void {
        if ($field->resolveFn && !$field->resolveFn instanceof ProxyResolver) {
            $field->resolveFn = new ProxyResolver($field->resolveFn);
        }
    }

}
