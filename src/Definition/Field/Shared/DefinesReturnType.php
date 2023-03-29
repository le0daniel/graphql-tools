<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Contract\TypeRegistry;

trait DefinesReturnType
{
    protected Type|Closure $ofType;

    final public function ofType(Type|Closure $resolveType): static
    {
        $this->ofType = $resolveType;
        return $this;
    }

    private function verifyTypeIsSet(): void {
        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }
    }
}