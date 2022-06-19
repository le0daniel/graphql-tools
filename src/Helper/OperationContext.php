<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Context;

/**
 * Class ExecutionContext
 *
 * @phan-read-only Context $context
 * @phan-read-only ExtensionManager $context
 *
 * @property-read Context $context
 * @property-read Extensions $extensions
 *
 */
final class OperationContext
{
    public function __construct(
        public readonly Context $context,
        public readonly Extensions $extensions
    )
    {
    }
}
