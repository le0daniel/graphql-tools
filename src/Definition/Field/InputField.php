<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;

class InputField extends GraphQlField
{
    public function toField(TypeRepository $repository): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'type' => $this->resolveType($repository, $this->resolveType),
            'description' => $this->computeDescription(),

            // Separate config keys for additional value
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            // Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);
    }
}