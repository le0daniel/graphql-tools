<?php

namespace GraphQlTools\Definition\Shared;

trait Describable {

    /**
     * Declares the description
     * @var string
     */
    protected string $description = 'No specific description available.';

    /**
     * Set the description of this Type
     *
     * @param string $description
     * @return $this
     */
    public function describedBy(string $description): self{
        $this->description = $description;
        return $this;
    }

}
