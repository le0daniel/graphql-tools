<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Contract\GraphQlContext;

/**
 * @internal
 */
readonly class OperationContext
{
    public function __construct(
        public GraphQlContext   $context,
        public Extensions       $extensions,
        public ExecutionManager $executor = new ExecutionManager(),
        public ValidationRules  $validationRules = new ValidationRules(),
    )
    {}
}
