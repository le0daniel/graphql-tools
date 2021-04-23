<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\Context;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRepository;

class QueryWithNormalTypeResolverTest extends ExecutionTestCase {

    protected function typeRepository(): TypeRepository {
        return new TypeRepository();
    }

    protected function queryType(): string {
        return QueryType::class;
    }

    public function testSimpleExecution(): void {
        $result = $this->queryExecutor->execute('query { whoami }', new Context());
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
    }

    public function testQueryWithType(): void {
        $result = $this->queryExecutor->execute('query { whoami, user { id, data } }', new Context());
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
        self::assertEquals(QueryType::USER_ID, $result->data['user']['id']);
        self::assertIsArray($result->data['user']['data']);
    }

    public function testQueryWithArgument(): void {
        $result = $this->queryExecutor->execute('query { whoami, user { id, data, name(name: "MY_NAME") } }', new Context());
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
        self::assertEquals(QueryType::USER_ID, $result->data['user']['id']);
        self::assertEquals('MY_NAME', $result->data['user']['name']);
        self::assertIsArray($result->data['user']['data']);
    }

    public function testQueryWithUnion(): void {
        $result = $this->queryExecutor->execute('query { animals { sound } }', new Context());
        $this->assertNoErrors($result);
    }
}
