<?php

namespace Connector\Schema;

use Connector\Type\JsonSchemaTypes;

/**
 * A generic and empty JSON Schema, for integrations that do not need a specific schema.
 */
class GenericSchema extends IntegrationSchema
{
    public function __construct($title = "Generic Schema")
    {
        $builder = new Builder("https://formassembly.com/generic/schema", $title);
        parent::__construct($builder->schema);
    }

    protected function getProperty(string $recordType, string $propertyName): ?array
    {
        return ["type" => JsonSchemaTypes::String];
    }
}