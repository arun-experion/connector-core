<?php
declare(strict_types=1);
namespace Connector\Record;

use ArrayAccess;
use Countable;

/**
 * A Recordset refers to a collection of Records extracted from the source integration.
 */
class Recordset implements ArrayAccess, Countable
{
    public array $records = [];

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->records[$offset]);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->records[$offset] ?? null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->records[] = $value;
        } else {
            $this->records[$offset] = $value;
        }
    }
    public function offsetUnset(mixed $offset): void
    {
        unset($this->records[$offset]);
    }
    public function count(): int
    {
        return count($this->records);
    }
}
