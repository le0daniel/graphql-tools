<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use DateTimeInterface;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesNotice;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\TypeRepository;
use JetBrains\PhpStorm\Pure;

abstract class GraphQlField
{
    use DefinesField, DefinesReturnType, DefinesMetadata, DefinesNotice;

    protected function __construct(public readonly string $name)
    {
    }

    #[Pure]
    final public static function withName(string $name): static
    {
        return new static($name);
    }

    abstract public function toFieldDefinition(TypeRepository $repository): FieldDefinition;
}