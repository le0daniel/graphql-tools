<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;
use JetBrains\PhpStorm\Pure;

class SimpleField implements Fieldable
{
    protected string|null $description = null;
    protected mixed $metadata = null;
    protected bool $isBeta = false;
    protected string|bool $deprecatedReason = false;
    protected \DateTimeInterface|null $removalDate = null;

    /** @var callable  */
    protected $resolveFunction;

    /** @var Type|callable  */
    protected mixed $resolveType;

    protected function __construct(protected string $name){}

    #[Pure]
    final public static function withName(string $name): static {
        return new static($name);
    }

    final public function withReturnType(Type|callable $resolveType): static {
        $this->resolveType = $resolveType;
        return $this;
    }

    final public function withDescription(string $description): static {
        $this->description = $description;
        return $this;
    }

    final public function withMetadata(mixed $metadata): static {
        $this->metadata = $metadata;
        return $this;
    }

    final public function isDeprecated(string $reason, \DateTimeInterface $removalDate): static {
        $this->deprecatedReason = $reason;
        $this->removalDate = $removalDate;
        return $this;
    }

    final public function isBeta(): static {
        $this->isBeta = true;
        return $this;
    }

    public function withResolver(callable $resolveFunction): self {
        $this->resolveFunction = $resolveFunction;
        return $this;
    }

    private function computeDescription(): ?string {
        $descriptionParts = [];

        if ($this->deprecatedReason) {
            $descriptionParts[] = '**DEPRECATED**, Removal Date: ' . $this->removalDate->format('Y-m-d') . '.';
        }

        if ($this->isBeta) {
            $descriptionParts[] = '**BETA**:';
        }

        if ($this->description) {
            $descriptionParts[] =  $this->description;
        }

        return empty($descriptionParts) ? null : implode(' ', $descriptionParts);
    }

    protected function getResolver(): ProxyResolver {
        return $this->resolveFunction instanceof ProxyResolver
            ? $this->resolveFunction
            : new ProxyResolver($this->resolveFunction);
    }

    public function toField(?string $name, TypeRepository $repository): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $this->getResolver(),
            'type' => $this->resolveType instanceof Type
                ? $this->resolveType
                : call_user_func($this->resolveType, $repository),
            'deprecationReason' => $this->deprecatedReason,
            'description' => $this->computeDescription(),
            //'args' => $this->a,

            // Separate config keys for additional value
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            // Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);
    }
}