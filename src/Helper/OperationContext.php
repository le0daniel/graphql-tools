<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Contract\GraphQlContext;

final readonly class OperationContext
{
    public function __construct(
        public GraphQlContext   $context,
        public Extensions $extensions
    )
    {
    }
}
