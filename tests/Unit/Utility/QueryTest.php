<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Query;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{

    /** @dataProvider createSignatureProvider */
    public function testCreateSignatureString(string $query, string $expected): void
    {
        self::assertEquals($expected, Query::createSignatureString($query));
    }

    public function createSignatureProvider(): array {
        return [
            'remove aliases and query' => [
                'query {value: whoami}', '{ whoami }'
            ],
            'sort fields' => [
                'query { 
                    myType {
                        b
                        a
                    }
                }', '{ myType { a b } }'
            ],
            'order inline fragment' => [
                'query {
                    type {
                        ... on User {
                            id
                        }
                        ... on Alpha {
                            b
                            a
                        }
                    }
                }',
                '{ type { ... on Alpha { a b } ... on User { id } } }'
            ],
            'order fragments' => [
                'query {
                    user {
                        ...BFragment
                        ...AFragment
                    }
                }
                
                fragment BFragment on User {
                    b
                    a
                }
                fragment AFragment on User {
                    b
                    a
                }',
                '{ user { ...AFragment ...BFragment } } fragment AFragment on User { a b } fragment BFragment on User { a b }',
            ],
            'hide literals' => [
                'query {
                    preview(type: "small", count: 10, object: {type: "a"}, list: [{a: "string"}])
                }',
                '{ preview(count: 0, list: [], object: {}, type: "") }'
            ],
            'variables' => [
                'query ($var1: ID!, $bvar: ID!) {
                    preview(type: $var1, count: $bvar)
                }',
                'query ($bvar: ID!, $var1: ID!) { preview(count: $bvar, type: $var1) }'
            ]
        ];
    }

    /** @dataProvider sameSignatureDataProvider */
    public function testSameSignature(string ... $queries): void {
        $signatures = array_map(fn(string $query): string => Query::createSignatureString($query), $queries);
        self::assertCount(1, array_unique($signatures));
    }

    public function sameSignatureDataProvider(): array {
        return [
            'with aliases' => [
                'query AuthorForPost {
                  post(id: "my-post-id") {
                    author
                  }
                }',
                'query AuthorForPost {
                  post(id: "my-post-id") {
                    writer: author
                  }
                }'
            ]
        ];
    }

    /** @dataProvider getQueryNameDataProvider */
    public function testGetQueryName(?string $expectedName, string $query): void {
        self::assertEquals($expectedName, Query::getQueryName($query));
    }

    public function getQueryNameDataProvider(): array {
        return [
            'query without name' => [
                null, 'query { currentName { id } }'
            ],
            'mutation without name' => [
                null, 'mutation { currentName { id } }'
            ],
            'query with name' => [
                'MyName', 'query MyName{ currentUser {id} }'
            ],
            'query with name and variables' => [
                'MyName', 'query MyName($input: CurrentUserInput!){ currentUser {id} }'
            ],
            'mutation with name' => [
                'MyName', 'mutation MyName{ currentUser {id} }'
            ],
            'mutation with name and variables' => [
                'MyName', 'mutation MyName($input: CurrentUserInput!){ currentUser {id} }'
            ],
        ];
    }
}
