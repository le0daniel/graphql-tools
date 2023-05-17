<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Contract\GraphQlContext;

final class OperationContext
{
    public function __construct(
        public readonly GraphQlContext   $context,
        public readonly ExtensionManager $extensionManager
    )
    {
    }
}
