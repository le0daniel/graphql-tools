<?php

declare(strict_types=1);

namespace GraphQlTools\Execution;

use GraphQlTools\Context;
use GraphQlTools\Execution\Extensions;

/**
 * Class ExecutionContext
 * @package GraphQlTools\Execution
 *
 * @phan-read-only Context $context
 * @phan-read-only ExtensionManager $context
 */
final class OperationContext {

    public function __construct(public Context $context, public Extensions $extensions){}

}
