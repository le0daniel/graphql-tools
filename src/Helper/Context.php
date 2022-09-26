<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Contract\GraphQlContext;

class Context implements GraphQlContext
{
    use HasDataloaders;
}
