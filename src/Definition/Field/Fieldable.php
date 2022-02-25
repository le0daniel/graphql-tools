<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\TypeRepository;

interface Fieldable
{
    public function toField(?string $name, TypeRepository $repository): FieldDefinition;
}