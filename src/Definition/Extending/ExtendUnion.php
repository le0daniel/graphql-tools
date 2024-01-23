<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use GraphQlTools\Contract\ExtendsGraphQlDefinition;

class ExtendUnion implements ExtendsGraphQlDefinition
{
    private string $typeName;
    private \Closure $resolver;

    public function __construct(private readonly string $unionName)
    {
    }


    /**
     * Return the union to be extended
     * @return string
     */
    public function typeName(): string
    {
        return $this->unionName;
    }

    /**
     * Return the typename that should be added to the union.
     * @param string $typeName
     * @param \Closure $resolver
     * @return ExtendUnion
     */
    public function withTypeAndResolver(string $typeName, \Closure $resolver): self
    {
        $this->resolver = $resolver;
        $this->typeName = $typeName;
        return $this;
    }

    public function getResolver(): \Closure
    {
        return $this->resolver;
    }

    public function getPossibleTypeName(): string
    {
        return $this->typeName;
    }

}