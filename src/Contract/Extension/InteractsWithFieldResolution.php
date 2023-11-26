<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Extension;

use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

interface InteractsWithFieldResolution
{
    public function visitField(VisitFieldEvent $event): void;
}