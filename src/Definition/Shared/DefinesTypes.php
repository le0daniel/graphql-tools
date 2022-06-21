<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Helper\TypeRegistry;

/** @property-read TypeRegistry $typeRegistry */
trait DefinesTypes
{
    protected function initTypes(array $typeDeclarations): array {
        return array_map([$this, 'declarationToType'], $typeDeclarations);
    }

    protected function declarationToType(mixed $declaration): mixed
    {
        if ($declaration instanceof Type || $declaration instanceof Closure) {
            return $declaration;
        }

        if (is_string($declaration)) {
            return $this->typeRegistry->type($declaration);
        }

        throw DefinitionException::from($declaration, 'string (TypeName or TypeClassName)', Type::class);
    }

}