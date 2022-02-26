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

    private function getContextualDeferredLoader(array $arguments, Context $context, ResolveInfo $resolveInfo): ContextualLoader {
        $key = Paths::toString($resolveInfo->path) . ':' . json_encode($arguments);

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualLoader($this->resolveAggregated, $arguments, $context);
        }

        return $this->deferredLoaders[$key];
    }

    /**
     * Callable fn(array $queuedData, array $validatedArguments, Context $context) => mixed
     *
     * @param callable $callable
     * @return $this
     */
    public function resolveAggregated(callable $callable): static {
        $this->resolveAggregated = function (array $queuedData, array $arguments, Context $context) use ($callable) {
            $validatedArguments = $this->validateArguments($arguments);
            return $callable($queuedData, $validatedArguments, $context);
        };
        return $this;
    }

    /**
     * This function is responsible for returning the correct data for the current element.
     * Callable fn(mixed $typeData, array $loadedData, Context $context) => mixed
     *
     * @param callable $callable
     * @return $this
     */
    public function resolveItem(callable $callable): static {
        $this->resolveItem = $callable;
        return $this;
    }

    public function toField(TypeRepository $repository): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'type' => $this->resolveType($repository),
            'deprecationReason' => $this->deprecatedReason,
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($repository),
            'resolve' => new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
                return $this->getContextualDeferredLoader($arguments, $context, $resolveInfo)
                    ->defer($data, $this->resolveItem);
            }),
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            // Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);

        // TODO: Implement toField() method.
    }
}