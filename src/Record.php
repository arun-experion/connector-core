<?php

namespace Connector;

use Connector\Exceptions\InvalidMappingException;
use Connector\Record\RecordKey;

/**
 * A Record refers to a set of key/value pairs extracted from the source integration and available for data mapping.
 */
final class Record
{
    public string $recordType;
    public RecordKey $key;
    public array $data = [];

    /**
     * @param \Connector\Record\RecordKey $key
     * @param array                       $data Associative array of key/value pairs.
     */
    public function __construct(RecordKey $key, array $data)
    {
        $this->recordType = $key->recordType;
        $this->key  = $key;
        $this->data = $data;
    }

    /**
     * @throws \Connector\Exceptions\InvalidMappingException
     */
    public function getValue(string $id): mixed
    {
        if(array_key_exists($id, $this->data)) {
            return $this->data[$id];
        }
        throw new InvalidMappingException(sprintf("Field '%s' not found in record", $id));
    }

    public function getValues(): array
    {
        return $this->data;
    }

    public function getKey(): RecordKey
    {
        return $this->key;
    }

}
