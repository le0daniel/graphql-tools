<?php

declare(strict_types=1);

namespace GraphQlTools\Extension;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Data\Models\Message;
use GraphQlTools\Utility\Fields;

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

    public function jsonSerialize(): mixed {
        return $this->messages;
    }

    public function fieldResolution(FieldResolutionEvent $event): ?\Closure {

        // Adds a message if the field is marked as deprecated
        if ($event->info->fieldDefinition->isDeprecated()) {
            $this->messages[] = Message::deprecated($event->info);
        }

        // Adds a message if the field contains the isBeta flag in the config
        if (Fields::isBetaField($event->info->fieldDefinition)) {
            $this->messages[] = Message::beta($event->info);
        }

        if ($notice = Fields::getFieldNotice($event->info->fieldDefinition)) {
            $this->messages[] = Message::notice($notice);
        }

        return null;
    }
}
