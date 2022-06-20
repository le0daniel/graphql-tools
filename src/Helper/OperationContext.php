<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Context;

final class OperationContext
{
    public function __construct(
        public readonly Context          $context,
        public readonly ExtensionManager $extensionManager
    )
    {
    }
}
