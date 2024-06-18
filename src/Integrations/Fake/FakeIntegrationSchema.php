<?php

namespace Connector\Integrations\Fake;

use Connector\Schema\Builder\RecordProperties;
use Connector\Schema\Builder\RecordProperty;
use Connector\Schema\Builder\RecordType;
use Connector\Schema\Builder\RecordTypes;
use Connector\Schema\Builder;
use Connector\Schema\IntegrationSchema;
use Connector\Type\JsonSchemaFormats;
use Connector\Type\JsonSchemaTypes;


class FakeIntegrationSchema extends IntegrationSchema
{
    private mixed $db = null;

    public function __construct(mixed $db)
    {
        $id          = "https://formassembly.com/fake/schema.json";
        $title       = "Fake Schema - For testing only";
        $this->db    = $db;
        $recordTypes = $this->getRecordTypes();

        $this->setLocale('en-US');
        $this->setTimeZone('UTC');

        $builder = new Builder($id, $title, $recordTypes);
        parent::__construct($builder->schema);
    }

    private function getRecordTypes(): RecordTypes {
        $recordTypes = new RecordTypes();
        $statement   = $this->db->prepare("SELECT * FROM sqlite_master");
        $statement->execute();
        $schema = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($schema as $table) {
            $properties = new RecordProperties();

            if(!str_starts_with($table['name'],"sqlite_")) {

                foreach ($this->getColumnsFromTable($table['name']) as $column) {
                    $properties->add(
                        new RecordProperty($column['name'], array_merge(
                            $this->sqlTypeToJsonSchemaType($column['type']),
                            ["title"  => $column['name']]))
                    );
                }

                $recordType = new RecordType($table['name'], $properties);
                $recordTypes->add($recordType);
            }
        }
        return $recordTypes;
    }

    public function getColumnsFromTable(string $tableName): array
    {
        $statement = $this->db->prepare("PRAGMA table_info($tableName);");
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function sqlTypeToJsonSchemaType($type): array
    {
        return match (strtolower($type)) {
            "integer"   => ["type" => JsonSchemaTypes::Number],
            "date"      => ["type" => JsonSchemaTypes::String, "format" => JsonSchemaFormats::Date],
            "datetime"  => ["type" => JsonSchemaTypes::String, "format" => JsonSchemaFormats::DateTime],
            default     => ["type" => JsonSchemaTypes::String, "format" => JsonSchemaFormats::CommaSeparated],
        };
    }
}
