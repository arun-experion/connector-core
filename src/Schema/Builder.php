<?php

namespace Connector\Schema;

use Connector\Schema\Builder\RecordProperties;
use Connector\Schema\Builder\RecordProperty;
use Connector\Schema\Builder\RecordType;
use Connector\Schema\Builder\RecordTypes;

/**
 * JSON Schema builder, for integrations that dynamically generate a JSON Schema during the discover() call.
 */
class Builder
{
    public string $id;
    public string $title;
    public RecordTypes $recordTypes;
    public RecordProperties $definitions;
    public array $schema;

    public function __construct(string $id, string $title, ?RecordTypes $recordTypes = null, ?RecordProperties $definitions = null) {

        $this->id = $id;
        $this->title = $title;
        $this->definitions = $definitions ?? new RecordProperties();
        $this->recordTypes = $recordTypes ?? new RecordTypes();
        $this->schema = $this->toArray();
    }

    public function toSchema(): IntegrationSchema
    {
        return new IntegrationSchema($this->toArray());
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
    }

    public function toArray(): array
    {
        return [
            '$schema' => "https://formassembly.com/connector/1.0/schema-integration",
            '$id'     => $this->id,
            "title"   => $this->title,
            "type"    => "array",
            "items"   => $this->recordTypes->toArray(),
            '$defs'   => $this->definitions->toArray()
        ];
    }

    /**
     * @param string|RecordType $recordType
     * @param RecordProperties|RecordProperty[]|null $properties
     * @return $this
     */
    public function addRecordType(mixed $recordType, mixed $properties = null): self
    {
        if($recordType instanceof RecordType) {
            $this->recordTypes->add($recordType);
        } else {
            $this->recordTypes->add( new RecordType($recordType, $properties) );
        }
        return $this;
    }

    public function addDefinitions(RecordProperties $definitions)
    {
        $this->definitions = $definitions;
    }

    public function addDefinition(RecordProperty $definition)
    {
        $this->definitions->add($definition);
    }

}
