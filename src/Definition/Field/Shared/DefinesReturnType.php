<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\TypeRegistry;

trait DefinesReturnType
{
    /** @var Type|callable|string */
    protected mixed $ofType;

    final public function ofType(Type|callable|string $resolveType): static
    {
        $this->ofType = $resolveType;
        return $this;
    }

    final protected function resolveReturnType(TypeRegistry $repository): mixed
    {
        if ($this->ofType instanceof Type) {
            return $this->ofType;
        }

        if ($this->ofType instanceof Closure) {
            return call_user_func($this->ofType, $repository);
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