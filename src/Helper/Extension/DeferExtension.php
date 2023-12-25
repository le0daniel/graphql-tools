<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Executor\Values;
use GraphQlTools\Contract\Events\VisitField;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;

class DeferExtension extends Extension implements InteractsWithFieldResolution
{
    private const DEFER_DIRECTIVE_NAME = 'defer';

    /**
     * Requires a low priority to work correctly. This should be the most important
     * extension.
     * @return int
     */
    public function priority(): int
    {
        return -100;
    }

    public function visitField(VisitField $event): void
    {
        if (!$event->canDefer() || !$event->hasDirective(self::DEFER_DIRECTIVE_NAME)) {
            return;
        }

        $arguments = $event->getDirectiveArguments(self::DEFER_DIRECTIVE_NAME);
        $isEnabled = $arguments['if'] ?? true;

        if ($isEnabled) {
            $event->defer($arguments['label'] ?? null);
        }
    }
}