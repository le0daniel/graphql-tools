<?php

declare(strict_types=1);

namespace GraphQlTools;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Utility\Paths;

class Context {

    /** @var DataLoader[] */
    private array $loaders = [];

    /**
     * Creates an instance of a class with some additional parameters. This is used by the
     * Context to create a specific DataLoader. DataLoaders with the same options and and
     * keys are shared during the resolution to resolve the N+1 issue when resolving data.
     *
     * The `options` are meant to pass in additional data to filter the query. For example
     * a user ID to only show data from one user or filtering. As graphql allows aliases,
     * this ensures that the same loader is only used if the options for the specific loaders
     * are the same.
     *
     * @param string $className
     * @param array $parameters
     * @return mixed
     */
    protected function makeDataLoaderInstance(string $className, array $parameters = []): mixed {
        return $parameters ? new $className($parameters['options'] ?? null) : new $className;
    }

    /**
     * Computes a cache key for a specific data loader given it's resolve info and options.
     * Be careful when implementing this yourself.
     *
     * @param ResolveInfo $info
     * @param string $className
     * @param array|null $options
     * @return string
     * @throws \JsonException
     */
    protected function dataLoaderKey(ResolveInfo $info, string $className, ?array $options = null): string {
        $optionsKey = $options ? json_encode($options, JSON_THROW_ON_ERROR) : 'none';
        $path = Paths::toString($info->path);
        return "{$className}::{$path}-{$optionsKey}";
    }

    /**
     * Returns an instance of a data loader. If already initialized, it will return the same dataloader that
     * was already used before.
     *
     * @param ResolveInfo $info
     * @param string $className
     * @param array|null $options
     * @return DataLoader
     * @throws \JsonException
     */
    final public function getDataLoader(ResolveInfo $info, string $className, ?array $options = null): DataLoader {
        $dataLoaderKey = $this->dataLoaderKey($info, $className, $options);

        if (!isset($this->loaders[$dataLoaderKey])) {
            $this->loaders[$dataLoaderKey] = $this->makeDataLoaderInstance($className, [
                'options' => $options
            ]);
        }

        return $this->loaders[$dataLoaderKey];
    }

    final public function getUsedLoaders(): array {
        return array_keys($this->loaders);
    }

}
