<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\UserType;
use GraphQlTools\Utility\TypeMap;

class CachedSchemaTest extends QueryTest
{

    protected function schema(): Schema
    {
        $federatedSchema = new FederatedSchema();

        foreach (TypeMap::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema') as $key => $value) {
            $federatedSchema->registerType($key, $value);
        }

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