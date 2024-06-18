<?php

use Connector\Type\DataType;
use Connector\Type\JsonSchemaFormats;
use Connector\Type\JsonSchemaTypes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Type\DataType
 */
final class DataTypeTest extends TestCase
{

    public function testConstructor(): void
    {
        $type = new DataType("number");
        $this->assertSame(JsonSchemaTypes::Number, $type->getType());

        $type = new DataType(JsonSchemaTypes::Integer);
        $this->assertSame(JsonSchemaTypes::Integer, $type->getType());

        $this->expectException(ValueError::class);
        $type = new DataType('bad type');
    }

    public function testFromJson(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "format": "date-time"}
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertSame(JsonSchemaTypes::String, $type->getType());
        $this->assertSame(JsonSchemaFormats::DateTime, $type->getFormat());
    }

    public function testOverwritingFromJson(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "format": "date-time"}
        JSON;

        $type = (new DataType())->fromJsonSchema($json);

        $json = <<<'JSON'
        { "type": "number"}
        JSON;

        $type->fromJsonSchema($json);

        $this->assertEquals(JsonSchemaFormats::None, $type->getFormat());
    }

    public function testArray(): void
    {
        $json = <<<'JSON'
        { "type": "array",
          "items": {
            "type": "string",
            "format": "date"
          }
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertEquals(JsonSchemaTypes::Array, $type->getType());
        $items = $type->getItems();
        $this->assertInstanceOf(DataType::class,$items);
        $this->assertEquals(JsonSchemaTypes::String, $items->getType());
        $this->assertEquals(JsonSchemaFormats::Date, $items->getFormat());
    }

    public function testTuple(): void
    {
        $json = <<<'JSON'
        { "type": "array",
          "prefixItems": [
          {
            "type": "number"     
          },
          {
            "type": "string"          
          }]
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertEquals(JsonSchemaTypes::Array, $type->getType());
        $items = $type->getPrefixItems();
        $this->assertCount(2,$items);
        $this->assertEquals(JsonSchemaTypes::Number, $items[0]->getType());
        $this->assertEquals(JsonSchemaTypes::String, $items[1]->getType());
    }


    public function testAllOf(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "allOf": [
            {"format": "comma-separated"},
            {"format": "plain-text"}         
          ]
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::CommaSeparated));
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::PlainText));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::DateTime));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::None));
    }

    public function testAnyOf(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "anyOf": [
            {"format": "comma-separated"},
            {"format": "plain-text"}         
          ]
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::CommaSeparated));
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::PlainText));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::DateTime));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::None));
    }

    public function testOneOf(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "oneOf": [
            {"format": "comma-separated"},
            {"format": "plain-text"}         
          ]
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::CommaSeparated));
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::PlainText));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::DateTime));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::None));
    }

    public function testFormat(): void
    {
        $json = <<<'JSON'
        { "type": "string",
          "format": "comma-separated"           
        }
        JSON;

        $type = (new DataType())->fromJsonSchema($json);
        $this->assertTrue($type->expectsFormat(JsonSchemaFormats::CommaSeparated));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::PlainText));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::DateTime));
        $this->assertFalse($type->expectsFormat(JsonSchemaFormats::None));
    }

}
