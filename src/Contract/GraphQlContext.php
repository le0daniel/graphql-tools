<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface GraphQlContext
{
    /**
     * @param string $classNameOrLoaderName
     * @return DataLoader
     */
    public function dataLoader(string $classNameOrLoaderName): DataLoader;
}