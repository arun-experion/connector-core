<?php

use Connector\Exceptions\DataConversionException;
use Connector\Type\DataType;
use Connector\Type\JsonSchemaTypes;
use Connector\Type\TypedValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Type\TypedValue
 * @uses \Connector\Type\DataType
 */
final class TypedValueTest extends TestCase
{

    public function testConstructor(): void
    {
        $type = new DataType(JsonSchemaTypes::Integer);
        $typedValue = new TypedValue("1", $type);
        $this->assertSame(1, $typedValue->value);

        $typedValueFromTypedValue = new TypedValue($typedValue);
        $this->assertSame(1, $typedValueFromTypedValue->value);
        $this->assertSame($type, $typedValueFromTypedValue->type);

        $otherType = new DataType(JsonSchemaTypes::Number);
        $otherTypedValue = new TypedValue($typedValue, $otherType);
        $this->assertSame(1.0, $otherTypedValue->value);
        $this->assertSame($otherType, $otherTypedValue->type);
    }

    public function testConstructorErrors()
    {
        $this->expectException(DataConversionException::class);
        $this->expectExceptionMessage("Cannot convert value of type 'object'");
        $typedValue = new TypedValue(new \StdClass());
    }
}
