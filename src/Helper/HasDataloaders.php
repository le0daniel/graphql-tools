<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\ExecutableByDataLoader;

trait HasDataloaders
{

    /**
     * All instances of Data loaders created.
     *
     * @var array
     */
    protected array $dataLoaderInstances = [];

    /**
     * Create an instance of a DataLoader executor, Must either be a Closue or
     * an instance extending the ExecutableByDataLoader contract. This is then
     * given to a new instance of a data loader.
     *
     * @param string $classNameOrLoaderName
     * @return Closure|ExecutableByDataLoader
     */
    protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure|ExecutableByDataLoader
    {
        return new $classNameOrLoaderName;
    }

    public function dataLoader(string $classNameOrLoaderName): DataLoader
    {
        if (!isset($this->dataLoaderInstances[$classNameOrLoaderName])) {
            $this->dataLoaderInstances[$classNameOrLoaderName] = new DataLoader(
                $this->makeInstanceOfDataLoaderExecutor($classNameOrLoaderName)
            );
        }

        return $this->dataLoaderInstances[$classNameOrLoaderName];
    }

}