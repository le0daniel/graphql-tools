<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Extension;

use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\ValidatedEvent;

interface ListensToLifecycleEvents
{
    public function start(StartEvent $event): void;

    public function end(EndEvent $event): void;

    public function parsed(ParsedEvent $event): void;

    public function validated(ValidatedEvent $event): void;
}