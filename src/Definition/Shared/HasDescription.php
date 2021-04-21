<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

trait HasDescription {

    abstract protected function description(): string;

}
