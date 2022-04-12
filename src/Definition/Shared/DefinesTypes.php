<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\TypeRegistry;

/** @property-read  TypeRegistry $typeRepository */
trait DefinesTypes
{
    protected function initTypes(array $typeDeclarations): array {
        return array_map([$this, 'declarationToType'], $typeDeclarations);
    }

    protected function declarationToType(mixed $declaration): mixed
    {
        if ($declaration instanceof Type) {
            return $declaration;
        }

        if ($declaration instanceof Closure) {
            return $declaration($this->typeRepository);
        }

        if (is_string($declaration)) {
            return $this->typeRepository->type($declaration);
        }

        throw DefinitionException::from($declaration, 'string (TypeName or TypeClassName)', Type::class, 'fn(TypeRepository $typeRepository)');
    }

}