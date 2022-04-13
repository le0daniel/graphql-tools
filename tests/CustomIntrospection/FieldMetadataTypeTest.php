<?php declare(strict_types=1);

namespace GraphQlTools\Test\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\CustomIntrospection\FieldMetadataType;
use GraphQlTools\Helper\TypeTestCase;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Fields;

class FieldMetadataTypeTest extends TypeTestCase
{

    public function typeClassName(): string
    {
        return FieldMetadataType::class;
    }

    private function fieldDefinition(mixed $metadata = null): FieldDefinition {
        return FieldDefinition::create(Arrays::removeNullValues([
            'name' => 'myFieldName',
            'type' => Type::int(),
            Fields::METADATA_CONFIG_KEY => $metadata
        ]));
    }

    public function testNameField(){
        $fieldDefinition = $this->fieldDefinition();
        $result = $this->field('name')
            ->visit($fieldDefinition);
        self::assertEquals($fieldDefinition->name, $result);
    }

    public function testTypeField(){
        $fieldDefinition = $this->fieldDefinition();
        $result = $this->field('type')
            ->visit($fieldDefinition);

        self::assertEquals((string) Type::int(), $result);
    }

    public function testMetadataField(){
        $fieldDefinition = $this->fieldDefinition('my-metadata');
        $result = $this->field('metadata')
            ->visit($fieldDefinition);

        self::assertEquals('my-metadata', $result);
    }

    public function testEmptyMetadataField(){
        $fieldDefinition = $this->fieldDefinition();
        $result = $this->field('metadata')
            ->visit($fieldDefinition);

        self::assertEquals(null, $result);
    }
}