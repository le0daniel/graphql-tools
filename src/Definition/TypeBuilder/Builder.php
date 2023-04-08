<?php declare(strict_types=1);

namespace GraphQlTools\Definition\TypeBuilder;

use Closure;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Shared\DefinesBaseProperties;
use GraphQlTools\Definition\Shared\InitializesFields;

final class Builder implements DefinesGraphQlType
{
    use DefinesBaseProperties, InitializesFields;

    protected array $interfaces = [];
    protected ?Closure $fieldDefinitionClosure = null;

    private function __construct(
        public readonly string $name
    )
    {
    }

    public static function withName(string $name): self {
        return new self($name);
    }

    public function interfaces(string... $interfaceName): self {
        $this->interfaces = $interfaceName;
        return $this;
    }

    public function fields(Closure $closure): self {
        $this->fieldDefinitionClosure = $closure;
        return $this;
    }

    public function toDefinition(TypeRegistry $registry, array $injectedFields = []): ObjectType|InterfaceType|InputObjectType {
        $allFields = [$this->fieldDefinitionClosure, ...$injectedFields];


        return new ObjectType([
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'fields' => $this->initializeFields($registry, $allFields),
            'interfaces' => array_map(fn(string $typeName) => $registry->type($typeName), $this->interfaces),
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }
}