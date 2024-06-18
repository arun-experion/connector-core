<?php

namespace Connector\Schema\Builder;

/**
 * A set of record properties
 */
class RecordProperties
{
    private array $items = [];

    public function __construct(array $properties = [])
    {
        $this->items =  $properties;
    }
    public function add(RecordProperty $property): self
    {
        $this->items[$property->name] = $property;
        return $this;
    }

    public function toArray(): array
    {
        return array_map(function($item) { return $item->toArray(); }, $this->items);
    }
}