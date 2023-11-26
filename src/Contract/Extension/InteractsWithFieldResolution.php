<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Extension;

use GraphQlTools\Contract\Events\VisitField;

interface InteractsWithFieldResolution
{
    public function visitField(VisitField $event): void;
}