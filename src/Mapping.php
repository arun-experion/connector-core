<?php

namespace Connector;

use ArrayAccess;
use Connector\Mapping\Item;
use Countable;
use Iterator;


/**
 * Mapping is a set of MappingItem, which describes the data being passed to the target Integration.
 */
class Mapping implements Iterator, ArrayAccess, Countable
{
    public  array $items = [];
    private array $keys = [];
    private int $index;

    public function __construct(...$map)
    {
        foreach($map as $mapItem) {
            if ($mapItem instanceof Item) {
                $this->items[] = $mapItem;
            } elseif (is_array($mapItem)) {
                foreach($mapItem as $key => $value) {
                    $this->items[] = new Item($key, $value);
                }

            } elseif (is_string($mapItem)) {
                $this->items[] = new Item($mapItem);
            }
        }
        $this->keys = array_keys($this->items);
    }

    public function hasItem(string $key): bool
    {
        return count($this->getItemsByKey($key))>0;
    }

    /**
     * @param string $key
     *
     * @return Item[]
     */
    public function getItemsByKey(string $key): array
    {
        $items = [];
        foreach($this->items as $item) {
            if($item->key === $key) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getValuesByKey(string $key): array
    {
        return array_map(function(Item $item) {
            return $item->value;
        }, $this->getItemsByKey($key));
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ?Item
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
            $this->keys[]  = array_key_last($this->items);
        } else {
            $this->items[$offset] = $value;
            if(!in_array($offset, $this->keys)) $this->keys[] = $offset;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
        unset($this->keys[array_search($offset,$this->keys)]);
        $this->keys = array_values($this->keys);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function current(): Item
    {
        return $this->items[$this->keys[$this->index]];
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function key(): int
    {
        return $this->keys[$this->index];
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}
