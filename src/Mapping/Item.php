<?php

namespace Connector\Mapping;

/**
 * A key/value pair, part of a mapping.
 * The key is the name of the record's property (ie. a field, column, etc. depending on the type of integration)
 */
class Item
{
    public string $key;
    public mixed $value;
    public ?string $label;

    public function __construct(string $key, mixed $value = null, string $label = null)
    {
        $this->key   = $key;
        $this->value = $value;
        $this->label = $label;
    }
}
