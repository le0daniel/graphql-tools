<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\Type;
use GraphQlTools\TypeRepository;

final class WrappedType {

    /** @var callable */
    private $resolver;

    public function __construct(private string $typeNameOrClassname, callable $resolver) {
        $this->resolver = $resolver;
    }

    public function toType(TypeRepository $repository): Type {
        return call_user_func($this->resolver, $repository->type($this->typeNameOrClassname));
    }

}
