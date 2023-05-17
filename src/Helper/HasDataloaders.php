<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\ExecutableByDataLoader;

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
    protected function makeInstanceOfDataLoaderExecutor(string $key): Closure|ExecutableByDataLoader
    {
        return new $key;
    }

    public function dataLoader(string $key): DataLoader
    {
        if (!isset($this->dataLoaderInstances[$key])) {
            $this->dataLoaderInstances[$key] = new DataLoader(
                $this->makeInstanceOfDataLoaderExecutor($key)
            );
        }

        return $this->dataLoaderInstances[$key];
    }

}