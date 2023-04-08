<?php declare(strict_types=1);

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Utility\TypeMap;

require_once __DIR__ . '/vendor/autoload.php';

$schemaDirectory = __DIR__ . '/tests/Dummies/Schema';

$federatedSchema = new FederatedSchema();
$federatedSchema->registerTypes(TypeMap::createTypeMapFromDirectory($schemaDirectory));

$federatedSchema->register(new class () extends GraphQlInputType {

    protected function fields(TypeRegistry $registry): array
    {
        return [
            InputField::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->deprecated('Please do not use anymore')
        ];
    }

    public function getName(): string
    {
        return 'MyInputType';
    }

    protected function description(): string
    {
        return '';
    }
});

$federatedSchema->extendType('Query', fn(TypeRegistry $registry) => [
    Field::withName('test')
        ->ofType(Type::string())
        ->withArguments(
            InputField::withName('input')
                ->deprecated('Now')
                ->ofType(Type::nonNull($registry->type('MyInputType'))),
        )
        ->resolvedBy(fn($_, array $args): string => $args['input']['name']),
]);

$executor = new QueryExecutor();

$result = $executor->execute(
    $federatedSchema->createSchema('Query'),
    "query { test(input: {name: \"string\"}) }",
    new Context()
);

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
