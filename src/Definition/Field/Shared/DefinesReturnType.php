<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Helper\TypeRegistry;

trait DefinesReturnType
{
    protected Type|string|Closure $ofType;

    final public function ofType(Type|Closure|string $resolveType): static
    {
        $this->ofType = $resolveType;
        return $this;
    }

    private function verifyTypeIsSet(): void {
        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }
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
            Type::class,
            Closure::class,
            'string'
        );
    }

}