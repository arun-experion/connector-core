<?php

namespace Connector\Schema\Builder;

/**
 * A collection of Record Types.
 */
class RecordTypes
{
    public array $types = [];

    public function __construct(array $types = [])
    {
        $this->types =  $types;
    }

    public function add(RecordType $type): self
    {
        $this->types[$type->name] = $type;
        return $this;
    }

    public function toArray(): array
    {
        return array_map(function($type) { return $type->toArray(); }, $this->types);
    }

}