<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Executor\Values;
use GraphQlTools\Contract\Events\VisitField;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Utility\Directives;

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
        if (!$event->canDefer()) {
            return;
        }

        $directives = Directives::getNamesByResolveInfo($event->info);
        if (!in_array(self::DEFER_DIRECTIVE_NAME, $directives, true)) {
            return;
        }

        $arguments = Values::getDirectiveValues(
            $event->info->schema->getDirective(self::DEFER_DIRECTIVE_NAME),
            $event->info->fieldNodes[0],
            $event->info->variableValues,
        );

        $isEnabled = $arguments['if'] ?? true;
        if ($isEnabled) {
            $event->defer($arguments['label'] ?? null);
        }
    }
}