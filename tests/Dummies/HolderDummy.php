<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQlTools\Immutable\Holder;

final class HolderDummy extends Holder {

    public function create(array $fields): HolderDummy {
        return new self($fields);
    }

}
