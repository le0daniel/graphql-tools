<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class SimpleField extends GraphQlField
{
    use DefinesArguments;

    /**
     * @var callable
     */
    private $resolveFunction;

    /**
     * Callable fn(mixed $data, array $validatedArguments, Context $context, ResolveInfo $resolveInfo) => mixed
     *
     * @param callable $resolveFunction
     * @return $this
     */
    public function resolvedBy(callable $resolveFunction): self {
        if ($resolveFunction instanceof ProxyResolver) {
            throw new \RuntimeException("Invalid resolve function given. Expected callable, got proxy resolver.");
        }

        $this->resolveFunction = $resolveFunction;
        return $this;
    }

    protected function getResolver(): ProxyResolver {
        if (!$this->resolveFunction) {
            return new ProxyResolver();
        }

        return new ProxyResolver(function($data, array $arguments, Context $context, ResolveInfo $info) {
            return ($this->resolveFunction)(
                $data,
                $this->validateArguments($arguments),
                $context,
                $info
            );
        });
    }

    public function toField(TypeRepository $repository): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $this->getResolver(),
            'type' => $this->resolveType($repository),
            'deprecationReason' => $this->deprecatedReason,
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($repository),

            // Separate config keys for additional value
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            // Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);
    }
}