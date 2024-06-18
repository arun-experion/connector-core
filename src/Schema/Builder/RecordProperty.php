<?php

namespace Connector\Schema\Builder;

/**
 * Describes a record property (E.g. a column in a database table).
 */
class RecordProperty
{
    public string $name = "";
    public string $title = "";
    public array $attributes = [];

    public function __construct(string $name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = $name;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function mergeAttributes(array $attributes = []): self
    {
        $this->attributes = array_merge($attributes, $this->attributes);
        return $this;
    }

}