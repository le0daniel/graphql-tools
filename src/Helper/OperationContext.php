<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

final class OperationContext
{
    public function __construct(
        public readonly Context          $context,
        public readonly ExtensionManager $extensionManager
    )
    {
    }
}
