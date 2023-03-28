<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Validator\Rules\ValidationRule;
use JsonSerializable;

abstract class ContextualValidationRule extends ValidationRule implements JsonSerializable
{

    /**
     * Validation rules are able to show data in the result of the query under
     * the extensions field. This is the key under which data is potentially exposed.
     *
     * @return string
     */
    abstract public function key(): string;

    /**
     * Determines if the data should be exposed in the result.
     *
     * @param $context
     * @return bool
     */
    abstract public function isVisibleInResult($context): bool;

}