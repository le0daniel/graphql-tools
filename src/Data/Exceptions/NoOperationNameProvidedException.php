<?php declare(strict_types=1);

namespace GraphQlTools\Data\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

final class NoOperationNameProvidedException extends Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }
}