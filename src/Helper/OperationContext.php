<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

/**
 * @internal
 */
readonly class OperationContext
{
    public function __construct(
        public GraphQlContext   $context,
        public Extensions       $extensions,
        public ExecutionManager $executor = new ExecutionManager(),
    )
    {}

    public function getContext(): GraphQlContext
    {
        return $this->context;
    }

    public function willResolveField(VisitFieldEvent $event): void
    {
        $this->extensions->willResolveField($event);
    }

}
