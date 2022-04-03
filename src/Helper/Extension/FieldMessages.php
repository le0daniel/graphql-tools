<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Helper\Validation\CollectFieldMessagesValidation;

class FieldMessages extends Extension
{
    private CollectFieldMessagesValidation $fieldMessages;

    public function __construct()
    {
        $this->fieldMessages = new CollectFieldMessagesValidation();
    }

    public function key(): string
    {
        return 'messages';
    }

    public function addValidationRule(): ?ValidationRule
    {
        return $this->fieldMessages;
    }

    public function jsonSerialize(): mixed
    {
        return $this->fieldMessages->getMessages();
    }
}