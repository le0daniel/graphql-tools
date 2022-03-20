<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Context;
use GraphQlTools\Helper\Extensions;

/**
 * Class ExecutionContext
 * @package GraphQlTools\Execution
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
    ){}
}
