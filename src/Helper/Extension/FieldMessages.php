<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Data\Models\Message;
use GraphQlTools\Utility\Fields;
use GraphQlTools\Utility\Paths;

final class FieldMessages extends Extension {

    /**
     * An array containing messages which have been collected through query execution
     *
     * @var Message[]
     */
    private array $messages = [];
    private array $keyMap = [];

    public function key(): string{
        return 'messages';
    }

    public function jsonSerialize(): array
    {
        return $this->messages;
    }

    private function hasAlreadyMessagesForField(string $path): bool {
        return array_key_exists($path, $this->keyMap);
    }

    private function ensureMessagesAreOnlyCollectedOncePerField(string $path): void {
        $this->keyMap[$path] = true;
    }

    public function visitField(VisitFieldEvent $event): ?Closure {
        $path = Paths::toString($event->info->path);

        if ($this->hasAlreadyMessagesForField($path)) {
            return null;
        }
        $this->ensureMessagesAreOnlyCollectedOncePerField($path);

        if ($event->info->fieldDefinition->isDeprecated()) {
            $this->messages[] = Message::deprecated($event->info);
        }

        if (Fields::isBetaField($event->info->fieldDefinition)) {
            $this->messages[] = Message::beta($event->info);
        }

        if ($notice = Fields::getFieldNotice($event->info->fieldDefinition)) {
            $this->messages[] = Message::notice($notice);
        }

        return null;
    }
}
