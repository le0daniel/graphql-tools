<?php

declare(strict_types=1);

namespace Test;

use DG\BypassFinals;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * By default, all non-extendable classes use the final keyword. This ensures
 * that no such class is modified at runtime. This makes the code hard to Test
 * as we can not mock classes. Therefore, when unit tests are run, the final
 * keyword is dropped everywhere.
 */
BypassFinals::enable();
