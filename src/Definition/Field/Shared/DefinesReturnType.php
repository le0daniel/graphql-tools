<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\TypeRegistry;

trait DefinesReturnType
{
    protected Type|string|Closure $ofType;

    final public function ofType(Type|Closure|string $resolveType): static
    {
        $this->ofType = $resolveType;
        return $this;
    }

    final protected function resolveReturnType(TypeRegistry $repository): Closure|Type
    {
        if ($this->ofType instanceof Type || $this->ofType instanceof Closure) {
            return $this->ofType;
        }

        if (is_string($this->ofType)) {
            return $repository->type($this->ofType);
        }

        throw DefinitionException::from(
            $this->ofType,
            'string (TypeName or TypeClassName)',
            Type::class,
            'fn(TypeRepository $typeRegistry)'
        );
    }

}