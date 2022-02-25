<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use Attribute;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\GraphQlAttribute;

final class DummyAttribute extends GraphQlAttribute
{
    public readonly array $data;

    public function __construct(string ...$data)
    {
        $this->data = $data;
    }

    public function isExposedPublicly(FieldDefinition $fieldDefinition): bool
    {
        return true;
    }

    public function toIntrospectionMetadata(): mixed
    {
        return [
            'type' => 'dummy',
            'data' => $this->data,
        ];
    }

}