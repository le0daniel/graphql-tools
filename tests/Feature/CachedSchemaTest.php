<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Type\Schema;
use GraphQlTools\Helper\Registry\FederatedSchema;


class CachedSchemaTest extends QueryTest
{

    protected function schema(): Schema
    {
        $code = $this->federatedSchema()->cacheSchema();

        file_put_contents(__DIR__ . '/output.php', "<?php {$code}");

        $schema = FederatedSchema::fromCachedSchema(
            eval($code),
            'Query'
        );
        $schema->assertValid();
        return $schema;
    }

}