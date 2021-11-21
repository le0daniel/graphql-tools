<?php declare(strict_types=1);

namespace GraphQlTools\Framework;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\LazyRepository;
use Illuminate\Contracts\Foundation\Application;

final class LaravelTypeRepository extends LazyRepository
{

    public function __construct(private Application $application, array $typeResolutionMap)
    {
        parent::__construct($typeResolutionMap);
    }

    protected function makeInstanceOfType(string $className): Type
    {
        return $this->application->make($className, [
            'typeRepository' => $this
        ]);
    }

    public function makeField(string $className): GraphQlField
    {
        return $this->application->make($className);
    }



}