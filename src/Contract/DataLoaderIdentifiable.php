<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface DataLoaderIdentifiable
{
    public function dataLoaderIdentifier(): int|string;
}