<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Error\ClientAware;

final class InvalidArgumentException extends \Exception implements ClientAware
{

    public function __construct(string $fieldName, string $message, \Throwable $previous)
    {
        parent::__construct("Validation failed for '{$fieldName}': {$message}", 0, $previous);
    }

    public function isClientSafe()
    {
        return true;
    }

    public function getCategory()
    {
        return 'validation';
    }
}