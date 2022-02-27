<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesNotice
{

    protected string|null $notice = null;

    public function withNotice(string $notice): static {
        $this->notice = $notice;
        return $this;
    }

}