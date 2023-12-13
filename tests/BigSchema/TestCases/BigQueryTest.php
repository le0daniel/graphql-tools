<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema\TestCases;

final class BigQueryTest extends BigSchemaTestCase
{

    public function testSimpleQuery(): void {
        $result = $this->execute(<<<GRAPHQL
query {
    clubs(limit: 1) {
        id
        name
        city
    }
}
GRAPHQL);

        $this->assertNoErrors($result);
        self::assertCount(1, $result->data['clubs']);
    }

}