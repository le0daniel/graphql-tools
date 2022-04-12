<?php declare(strict_types=1);

namespace GraphQlTools\Test\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\CustomIntrospection\TypeMetadataType;
use GraphQlTools\Helper\TypeTestCase;
use GraphQlTools\Utility\Fields;

class TypeMetadataTypeTest extends TypeTestCase
{
    private const OBJECT_TYPE_NAME = '__Test__';

    protected function typeClassName(): string
    {
        return TypeMetadataType::class;
    }

    private function objectType(): ObjectType
    {
        return new ObjectType([
            'name' => self::OBJECT_TYPE_NAME,
            'fields' => [
                'new' => Type::string(),
                'next' => Type::string(),
            ],
            Fields::METADATA_CONFIG_KEY => ['test']
        ]);
    }

    public function testNameField(): void
    {
        $result = $this->field('name')
            ->visit($this->objectType());

        self::assertEquals(self::OBJECT_TYPE_NAME, $result);
    }

    public function testMetadataField(): void
    {
        $result = $this->field('metadata')
            ->visit($this->objectType());
        self::assertEquals(['test'], $result);
    }

    public function testFieldsField(): void
    {
        $result = $this->field('fields')
            ->visit($this->objectType());
        self::assertCount(2, $result);
        self::assertInstanceOf(FieldDefinition::class, $result['new']);
        self::assertInstanceOf(FieldDefinition::class, $result['next']);
    }

    public function testFieldByNameField(): void
    {
        $result = $this->field('fieldByName')
            ->visit($this->objectType(), ['name' => 'new']);
        self::assertInstanceOf(FieldDefinition::class, $result);

        $result = $this->field('fieldByName')
            ->visit($this->objectType(), ['name' => 'non-existant']);
        self::assertNull($result);
    }
}
