<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\InvalidArgumentException;
use GraphQlTools\TypeRepository;
use Throwable;

trait DefinesArguments
{
    /** @var Argument[] */
    protected array $arguments = [];

    final public function withArguments(Argument ...$arguments): static
    {
        $this->arguments = $arguments;
        return $this;
    }

    final protected function buildArguments(TypeRepository $typeRepository): ?array
    {
        if (empty($this->arguments)) {
            return null;
        }

        return array_map(fn(Argument $argument) => $argument->toInputFieldDefinitionArray($typeRepository), $this->arguments);
    }

    final protected function validateArguments(array $arguments): array
    {
        $validatedArguments = [];

        foreach ($this->arguments as $argument) {
            try {
                $value = $arguments[$argument->name] ?? null;
                $validatedArguments[$argument->name] = $argument->validateValue($value, $arguments);
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(
                    $argument->name,
                    $exception->getMessage(),
                    $exception
                );
            }
        }

        return $validatedArguments;
    }

}