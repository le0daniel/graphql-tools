<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Helper\DataLoader\SimpleDataLoader;
use JsonException;

trait HasDataloaders
{

    /**
     * All instances of Data loaders created.
     *
     * @var array<string, SimpleDataLoader>
     */
    protected array $dataLoaderInstances = [];

    /**
     * Create an instance of a DataLoader executor, Must either be a Closure or
     * an instance extending the ExecutableByDataLoader contract. This is then
     * given to a new instance of a DataLoader.
     *
     * @param string $key
     * @param array $arguments
     * @return Closure|ExecutableByDataLoader
     */
    abstract protected function makeInstanceOfDataLoaderExecutor(string $key, array $arguments): Closure|ExecutableByDataLoader;

    /**
     * Given a loader function or class, create an instance of a DataLoader.
     * Use this to customize what data loader you want to use or use your custom
     * implementation.
     *
     * Provided implementations:
     * - SimpleDataLoader: Does not cache results between layers.
     * - CachedDataLoader: Caches resolved items for the whole query.
     *
     * @param string $key
     * @param array $arguments
     * @param Closure|ExecutableByDataLoader $loader
     * @return DataLoader
     */
    protected function createDataLoaderInstance(string $key, array $arguments, Closure|ExecutableByDataLoader $loader): DataLoader {
        return new SimpleDataLoader($loader);
    }

    /**
     * Creates a new DataLoader.
     * Implement `makeInstanceOfDataLoaderExecutor(string $key, array $arguments)` to create an instance
     * Customize data loader class used by overwriting `createDataLoaderInstance`. By default, SimpleDataLoader
     * is used
     *
     * @throws JsonException
     */
    final public function dataLoader(string $key, array $arguments = []): DataLoader
    {
        $argumentsKey = empty($arguments)
            ? ''
            : '::' . json_encode($arguments, JSON_THROW_ON_ERROR);
        $cacheKey = $key . $argumentsKey;

        return $this->dataLoaderInstances[$cacheKey] ??= $this->createDataLoaderInstance(
            $key,
            $arguments,
            $this->makeInstanceOfDataLoaderExecutor($key, $arguments)
        );
    }

    public function clearDataLoaders(): void {
        $this->dataLoaderInstances = [];
    }

}