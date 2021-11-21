<?php

declare(strict_types=1);

namespace GraphQlTools\Extension;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Immutable\Message;

final class FieldMessages extends Extension {

    /**
     * An array containing messages which have been collected through query execution
     *
     * @var Message[]
     */
    private array $messages = [];

    public function key(): string{
        return 'messages';
    }

    public function jsonSerialize(): mixed{
        return $this->messages;
    }

    public function fieldResolution(int $eventTimeInNanoseconds, $typeData, array $arguments, ResolveInfo $info): ?\Closure {

        // Adds a message if the field is marked as deprecated
        if ($info->fieldDefinition->isDeprecated()) {
            $this->messages[] = Message::deprecated($info);
        }

        // Adds a message if the field contains the isBeta flag in the config
        if ($info->fieldDefinition->config[GraphQlField::BETA_FIELD_CONFIG_KEY] ?? false) {
            $this->messages[] = Message::beta($info);
        }

        return null;
    }
}
