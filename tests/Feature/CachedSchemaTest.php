<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Type\Schema;
use GraphQlTools\Helper\Registry\FederatedSchema;


class CachedSchemaTest extends QueryTest
{

    protected function schema(): Schema
    {
        $code = $this->federatedSchema()->cacheSchema();

        $federatedSchema->extendType(
            UserType::class,
            fn(TypeRegistry $registry) => [
                Field::withName('extended')
                    ->ofType(Type::string())
                    ->resolvedBy(fn() => 'extended'),
                Field::withName('closure')
                    ->ofType($registry->type(JsonScalar::class))
                    ->resolvedBy(fn() => 'closure')
            ],
        );

        $federatedSchema->extendType('User',fn(TypeRegistry $registry) => [
            Field::withName('byName')
                ->ofType(Type::string())
                ->resolvedBy(fn() => 'byName'),

            // Ensure circular dependencies work fine
            Field::withName('testCircular')
                ->ofType($registry->type('User')),

            'lazy' => fn() => Field::withName('lazy')
                ->ofType(Type::string())
                ->resolvedBy(fn() => 'lazy-field')
        ]);
        $code = $federatedSchema->cacheSchema();
        $schema = FederatedSchema::fromCachedSchema(
            eval($code),
            'Query'
        );
        $schema->assertValid();
        return $schema;
    }

}