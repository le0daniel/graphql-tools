<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Executor\Values;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
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

    private function shouldDefer(ResolveInfo $info): bool|string {
        $directives = Directives::getNamesByResolveInfo($info);
        if (!in_array(self::DEFER_DIRECTIVE_NAME, $directives, true)) {
            return false;
        }

        $arguments = Values::getDirectiveValues(
            $info->schema->getDirective(self::DEFER_DIRECTIVE_NAME),
            $info->fieldNodes[0],
            $info->variableValues,
        );

        $isEnabled = $arguments['if'] ?? true;
        if (!$isEnabled) {
            return false;
        }

        return $arguments['label'] ?? true;
    }

    public function visitField(VisitFieldEvent $event): void
    {
        if (!$event->hasBeenDeferred && $labelOrBool = $this->shouldDefer($event->info)) {
            $event->defer(is_string($labelOrBool) ? $labelOrBool : null);
        }
    }
}