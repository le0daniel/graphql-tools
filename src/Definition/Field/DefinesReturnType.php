<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\Type;
use GraphQlTools\TypeRepository;

trait DefinesReturnType
{

    /** @var Type|callable|string */
    protected mixed $ofType;

    final public function ofType(Type|callable|string $resolveType): static
    {
        $this->ofType = $resolveType;
        return $this;
    }

    final protected function resolveType(TypeRepository $repository): mixed
    {
        if ($this->ofType instanceof Type) {
            return $this->ofType;
        }

        if (is_callable($this->ofType)) {
            return call_user_func($this->ofType, $repository);
        }

        return $repository->type($this->ofType);
    }

}