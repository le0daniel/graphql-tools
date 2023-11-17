<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\ExecutableByDataLoader;
use JsonException;

trait HasDataloaders
{

    /**
     * All instances of Data loaders created.
     *
     * @var array<string, DataLoader>
     */
    protected array $dataLoaderInstances = [];

    /**
     * Create an instance of a DataLoader executor, Must either be a Closue or
     * an instance extending the ExecutableByDataLoader contract. This is then
     * given to a new instance of a data loader.
     *
     * @param string $key
     * @return Closure|ExecutableByDataLoader
     */
    protected function makeInstanceOfDataLoaderExecutor(string $key, array $arguments): Closure|ExecutableByDataLoader
    {
        return new $key(...$arguments);
    }

    /**
     * @throws JsonException
     */
    public function dataLoader(string $key, array $arguments = []): DataLoader
    {
        $argumentsKey = empty($arguments)
            ? ''
            : '::' . json_encode($arguments, JSON_THROW_ON_ERROR);
        $completeKey = $key . $argumentsKey;

        return $this->dataLoaderInstances[$completeKey] ??= new DataLoader(
            $this->makeInstanceOfDataLoaderExecutor($completeKey, $arguments ?? [])
        );
    }

}