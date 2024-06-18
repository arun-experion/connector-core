<?php

use Connector\Schema\Builder\RecordProperty;
use Connector\Schema\IntegrationSchema;
use Connector\Type\JsonSchemaTypes;
use PHPUnit\Framework\TestCase;
use Connector\Schema\Builder;

/**
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Type\DataType
 * @covers Connector\Schema\Builder\RecordProperties
 * @covers Connector\Schema\Builder\RecordProperty
 * @covers Connector\Schema\Builder\RecordType
 * @covers Connector\Schema\Builder\RecordTypes
 * @covers Connector\Schema\Builder
 */
class SchemaBuilderTest extends TestCase
{
    public function testConstructor(): void
    {
        $builder = new Builder("http://test","Test Schema");
        $schema  = $builder->toSchema();
        $this->assertInstanceOf(IntegrationSchema::class, $schema);
    }

    public function testAddRecordType(): void
    {
        $builder = new Builder("http://test","Test Schema");
        $firstName = new RecordProperty('FirstName', ['type' => 'string']);
        $lastName  = new RecordProperty('LastName',  ['type' => 'string']);
        $email     = new RecordProperty('Email',     ['type' => 'string']);
        $contact   = new Builder\RecordType("Contact");
        $contact->addProperty($firstName);
        $contact->addProperty($lastName);
        $contact->addAnyOfProperty($email);
        $builder->addRecordType($contact);

        $schema = <<<'JSON'
{
    "$defs": [],
    "$id": "http://test",
    "$schema": "https://formassembly.com/connector/1.0/schema-integration",
    "items": {
        "Contact": {
            "additionalProperties": false,
            "anyOf": [
                {
                    "properties": {
                        "Email": {
                            "type": "string"
                        }
                    }
                }
            ],
            "properties": {
                "FirstName": {
                    "type": "string"
                },
                "LastName": {
                    "type": "string"
                }
            },
            "type": "object"
        }
    },
    "title": "Test Schema",
    "type": "array"
}
JSON;
        $this->assertJsonStringEqualsJsonString($schema, $builder->toJson());
        $this->assertInstanceOf(IntegrationSchema::class, $builder->toSchema());
        $this->assertEquals(JsonSchemaTypes::String, $builder->toSchema()->getDataType('Contact', 'FirstName')->getType());
        $this->assertEquals(JsonSchemaTypes::String, $builder->toSchema()->getDataType('Contact', 'Email')->getType());
    }
}
