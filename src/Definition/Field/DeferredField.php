<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\DataLoader\ContextualLoader;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;
use GraphQlTools\Utility\Paths;

class DeferredField extends GraphQlField
{
    use HasArguments;

    /** @var ContextualLoader[] */
    private array $deferredLoaders = [];

    /** @var callable */
    private $resolveItem;

    /** @var callable */
    private $resolveAggregated;

    public function withResolver(callable $resolveFunction): static
    {
        throw new \RuntimeException("With resolve is not available for deferred fields. Use `resolveAggregated` and `resolveItems` instead.");
    }

    private function getDeferredLoader(array $arguments, Context $context, ResolveInfo $resolveInfo): ContextualLoader {
        $key = Paths::toString($resolveInfo->path) . ':' . json_encode($arguments);

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualLoader($this->resolveAggregated, $arguments, $context);
        }

        return $this->deferredLoaders[$key];
    }

    public function resolveAggregated(callable $callable): static {
        $this->resolveAggregated = function (mixed $queuedData, array $arguments, Context $context) use ($callable) {
            $validatedArguments = $this->validateArguments($arguments);
            return $callable($queuedData, $validatedArguments, $context);
        };
        return $this;
    }

    public function resolveItem(callable $callable): static {
        $this->resolveItem = $callable;
        return $this;
    }

    public function toField(TypeRepository $repository): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'type' => $this->resolveType($repository, $this->resolveType),
            'deprecationReason' => $this->deprecatedReason,
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($repository),
            'resolve' => new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
                return $this->getDeferredLoader($arguments, $context, $resolveInfo)
                    ->defer($data, $this->resolveItem);
            }),
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            // Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);

        // TODO: Implement toField() method.
    }
}