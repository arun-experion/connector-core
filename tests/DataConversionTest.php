<?php

use Connector\Exceptions\DataConversionException;
use Connector\Type\DataType;
use Connector\Type\JsonSchemaFormats;
use Connector\Type\JsonSchemaTypes;
use Connector\Type\TypedValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Type\TypedValue
 * @covers \Connector\Type\DataType
 */
final class DataConversionTest extends TestCase
{


    /**
     * @dataProvider Types
     */
    public function testTypeConversion($expected, $input, $fromType, $toType, $locale = "en-US"): void
    {
        $type       = new DataType($fromType);
        $type->setLocale($locale);
        $typedValue = new TypedValue($input, $type);
        $targetType = new DataType($toType);
        $targetType->setLocale($locale);
        $this->assertSame($expected, $typedValue->convert($targetType)->value);

    }

    public static function Types(): array
    {
        return [
            "Number to Number"                => [2.3, 2.3, "number", "number"],
            "Number to Integer"               => [2, 2.3, "number", "integer"],
            "Number to Integer (no rounding)" => [2, 2.6, "number", "integer"],
            "Number to Boolean #1"            => [true, 1.1, "number", "boolean"],
            "Number to Boolean #2"            => [false, 0.0, "number", "boolean"],
            "Number to String"                => ["2.3", 2.3, "number", "string"],
            "Number to String (fr-FR locale)" => ["2,3", 2.3, "number", "string", "fr-FR"],
            "Integer to Number"               => [2.0, 2, "integer", "number"],
            "Integer to Integer"              => [2, 2, "integer", "integer"],
            "Integer to Boolean #1"           => [true, 1, "integer", "boolean"],
            "Integer to Boolean #2"           => [false, 0, "integer", "boolean"],
            "Integer to String"               => ["2", 2, "integer", "string"],
            "Boolean to Number #1"            => [1.0, true, "boolean", "number"],
            "Boolean to Number #2"            => [0.0, false, "boolean", "number"],
            "Boolean to Integer #1"           => [1, true, "boolean", "integer"],
            "Boolean to Integer #2"           => [0, FALSE, "boolean", "integer"],
            "Boolean to Boolean #1"           => [true, true, "boolean", "boolean"],
            "Boolean to Boolean #2"           => [false, false, "boolean", "boolean"],
            "Boolean to String #1"            => ["1", true, "boolean", "string"],
            "Boolean to String #2"            => ["0", false, "boolean", "string"],
            "Coerced Number to Integer"       => [2, "2.0", "number", "integer"],
            "Coerced Integer to Number"       => [2.0, "2", "integer", "number"],
            "Coerced Number to String"        => ["2.3", "2.3", "number", "string"],
            "Coerced Integer to String"       => ["2", "2", "integer", "string"],
            "String to String Array"          => [["abc"], "abc", "string", "array"],
            "Integer to Integer Array"        => [[2], 2, "integer", "array"],
            "Number to Number Array"          => [[2.0], 2.0, "number", "array"],
            "Boolean to Boolean Array"        => [[true], true, "boolean", "array"],
        ];
    }

    public function testArrayToArrayConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::Integer);
        $sourceType->setItems($arrayType);
        $typedValue = new TypedValue([1,4,7], $sourceType);

        $targetType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::String);
        $targetType->setItems($arrayType);

        $this->assertSame(["1","4","7"], $typedValue->convert($targetType)->value);

        $this->assertSame([1,4,7], $typedValue->convert($sourceType)->value);
    }

    public function testArrayToIntConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::Integer);
        $sourceType->setItems($arrayType);

        $typedValue = new TypedValue([1,4,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::Integer);

        // Keeps only first value of array.
        $this->assertSame(1, $typedValue->convert($targetType)->value);
    }

    public function testArrayToNumberConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::Integer);
        $sourceType->setItems($arrayType);

        $typedValue = new TypedValue([1,4,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::Number);

        // Keeps only first value of array.
        $this->assertSame(1.0, $typedValue->convert($targetType)->value);
    }

    public function testArrayToBooleanConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::Integer);
        $sourceType->setItems($arrayType);

        $typedValue = new TypedValue([1,4,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::Boolean);

        // Keeps only first value of array.
        $this->assertSame(true, $typedValue->convert($targetType)->value);
    }


    public function testIntArrayToStringConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::Integer);
        $sourceType->setItems($arrayType);
        $typedValue = new TypedValue([1,4,0,7], $sourceType);

        $targetType = new DataType(JsonSchemaTypes::String);
        $this->assertSame("1407", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue([1,4,0,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::SemiColonSeparated);
        $this->assertSame("1; 4; 0; 7", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue([1,4,0,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::CommaSeparated);
        $this->assertSame("1, 4, 0, 7", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue([1,4,0,7], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::SpaceSeparated);
        $this->assertSame("1 4 0 7", $typedValue->convert($targetType)->value);
    }

    public function testStringArrayToStringConversion():void
    {
        $sourceType = new DataType(JsonSchemaTypes::Array);
        $arrayType  = new DataType(JsonSchemaTypes::String);
        $sourceType->setItems($arrayType);

        $typedValue = new TypedValue([""], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String);
        $this->assertSame("", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue(["ab"], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String);
        $this->assertSame("ab", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue(["ab","","cd", "ef"], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String);
        $this->assertSame("abcdef", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue(["ab","","cd", "ef"], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::SemiColonSeparated);
        $this->assertSame("ab; cd; ef", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue(["ab","","cd", "ef"], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::CommaSeparated);
        $this->assertSame("ab, cd, ef", $typedValue->convert($targetType)->value);

        $typedValue = new TypedValue(["ab","","cd", "ef"], $sourceType);
        $targetType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::SpaceSeparated);
        $this->assertSame("ab cd ef", $typedValue->convert($targetType)->value);
    }

    public function testPlainTextConversion(): void
    {
        $type = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::PlainText);
        $typedValue = new TypedValue("<p>test</p><br> <br/> <BR > <h1>other</h1>", $type);
        $this->assertSame("test\n \n \n other", $typedValue->value);
    }

    /**
     * @dataProvider DateTypes
     */
    public function testDateTypesConversion($expected, $input, $sourceFormat, $targetFormat): void
    {
        $sourceType = new DataType("string", $sourceFormat);
        $typedValue = new TypedValue($input, $sourceType);
        $targetType = new DataType("string", $targetFormat);

        $this->assertSame($expected, $typedValue->convert($targetType)->value);
    }

    public static function DateTypes(): array
    {
        return [
            "Date to DateTime"     => ["2023-12-31T00:00:00+0000", "2023-12-31", "date", "date-time"],
            "Date to Date"         => ["2023-12-31", "2023-12-31", "date", "date"],
            // skipped - Test sensitive to ICU library version. Require ICU >= 72.1
            // "DateTime to Date"     => ["2023-12-31", "2023-12-31T00:00:00+0000", "date-time", "date"],
            // skipped - Test sensitive to ICU library version. Require ICU >= 72.1
            // "DateTime to DateTime" => ["2023-12-31T00:00:00+0000", "2023-12-31T00:00:00+0000", "date-time", "date-time"],
        ];
    }

    /**
     * @dataProvider Dates
     */
    public function testLocalDateConversion($pattern, $input, $sourceLocale, $targetLocale): void
    {
        $sourceType = new DataType("string", "local-date");
        $sourceType->setLocale($sourceLocale);
        $typedValue = new TypedValue($input, $sourceType);
        $targetType = new DataType("string", 'local-date');
        $targetType->setLocale($targetLocale);
        $this->assertMatchesRegularExpression($pattern, $typedValue->convert($targetType)->value);
    }

    /**
     * Note use of regex to match both 2 digits and 4 digits dates in FR locale. This accounts
     * for a change in behavior in the ICU library between latest and version currently in production (v50.2)
     */
    public static function Dates(): array
    {
        return [
            "Date conversion US to US" => ["/^12\/31\/23$/", "12/31/2023", "en-US", "en-US"],
            "Date conversion US to FR" => ["/^31\/12\/(20)?23$/", "12/31/2023", "en-US", "fr-FR"],
            "Date conversion US to DE" => ["/^31\.12\.23$/", "12/31/2023", "en-US", "de-DE"],
            "Date conversion FR to FR" => ["/^31\/12\/(20)?23$/", "31/12/2023", "fr-FR", "fr-FR"],
            "Date conversion FR to US" => ["/^12\/31\/23$/", "31/12/2023", "fr-FR", "en-US"],
            "Date conversion FR to DE" => ["/^31\.12\.23$/", "31/12/2023", "fr-FR", "de-DE"],
            "Date conversion DE to DE" => ["/^31\.12\.23$/", "31.12.2023", "de-DE", "de-DE"],
            "Date conversion DE to US" => ["/^12\/31\/23$/", "31.12.2023", "de-DE", "en-US"],
            "Date conversion DE to FR" => ["/^31\/12\/(20)?23$/", "31.12.2023", "de-DE", "fr-FR"],
        ];
    }

    /**
     * @dataProvider DateTimesToDates
     */
    public function testTimeZoneToDateConversion($expected, $input, $sourceTimeZone, $targetTimeZone)
    {
        $sourceType = new DataType("string", "date-time");
        $sourceType->setTimeZone($sourceTimeZone);
        $typedValue = new TypedValue($input, $sourceType);
        $targetType = new DataType("string", "local-date");
        $targetType->setTimeZone($targetTimeZone);

        $this->assertSame($expected, $typedValue->convert($targetType)->value);
    }

    public static function DateTimesToDates(): array
    {
        return [
            "Time-Zone conversion #1" => ["1/1/24", "12/31/2023 5:30pm", "America/Los_Angeles", "Pacific/Auckland"],
            "Time-Zone conversion #2" => ["12/31/23", "12/31/2023 5:30pm", "America/Los_Angeles", "America/Indianapolis"]
        ];
    }

    /**
     * @dataProvider DateTimesToDateTimes
     */
    public function testTimeZoneToDateTimeConversion($pattern, $input, $sourceTimeZone, $targetTimeZone)
    {
        $sourceType = new DataType("string", "date-time");
        $sourceType->setTimeZone($sourceTimeZone);
        $typedValue = new TypedValue($input, $sourceType);
        $targetType = new DataType("string","local-date-time");
        $targetType->setTimeZone($targetTimeZone);

        $this->assertMatchesRegularExpression($pattern, $typedValue->convert($targetType)->value);

    }

    /**
     * Note regex matching to catch different behavior in ICU library v73.2 and prior versions.
     * Whitespace before AM/PM is either an ascii space, or unicode \u{202F}
     */
    public static function DateTimesToDateTimes(): array
    {
        return [
            "Time-Zone conversion #1" => ["/^1\/1\/24, 2:30(\u{202F}|\s)PM$/", "12/31/2023 5:30pm", "America/Los_Angeles", "Pacific/Auckland"],
            "Time-Zone conversion #2" => ["/^12\/31\/23, 8:30(\u{202F}|\s)PM$/", "12/31/2023 5:30pm", "America/Los_Angeles", "America/Indianapolis"],
            "Time-Zone conversion #3" => ["/^12\/31\/23, 10:30(\u{202F}|\s)PM$/", "12/31/2023 5:30pm", "America/Indianapolis", "UTC"],
            "Time-Zone conversion #4" => ["/^12\/31\/23, 9:30(\u{202F}|\s)PM$/", "12/31/2023 5:30pm", "America/Indianapolis", "GMT-1"]
        ];
    }

    /**
     * @dataProvider TypeConversionErrors
     */
    public function testTypeConversionErrors($error, $input, $type, $format)
    {
        $this->expectException($error);
        $this->expectExceptionMessage("Cannot convert '$input' to {$type->value}");
        $type = new DataType($type, $format);
        $typedValue = new TypedValue($input, $type);
    }

    public static function TypeConversionErrors(): array
    {
        return [
            "Unsupported Object JSON Type"  => [ DataConversionException::class, "any", JsonSchemaTypes::Object, JsonSchemaFormats::None],
            "Unsupported Null JSON Type"    => [ DataConversionException::class, "any", JsonSchemaTypes::Null, JsonSchemaFormats::None],
            "Non numeric string to Integer" => [ DataConversionException::class, "any", JsonSchemaTypes::Integer, JsonSchemaFormats::None],
            "Non numeric string to Number"  => [ DataConversionException::class, "any", JsonSchemaTypes::Number, JsonSchemaFormats::None],
        ];
    }

    /**
     * @dataProvider dataTypes
     */
    public function testDataTypeConversion(
        JsonSchemaTypes $sourceTypeValue,
        mixed $sourceValue,
        JsonSchemaTypes $targetTypeValue,
        JsonSchemaTypes $targetItemTypeValue,
        array $expectedValue
    ): void {
        $sourceType = new DataType($sourceTypeValue);
        $typedValue = new TypedValue($sourceValue, $sourceType);

        $targetType = new DataType($targetTypeValue,);
        $arrayType  = new DataType($targetItemTypeValue);
        $targetType->setItems($arrayType);

        $this->assertSame($expectedValue, $typedValue->convert($targetType)->value);
    }

    public static function dataTypes(): array
    {
        return [
            "Integer to String Array Conversion" => [
                JsonSchemaTypes::Integer,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::String,
                ['1'],
            ],

            "Integer to Number Array Conversion" => [
                JsonSchemaTypes::Integer,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Number,
                [1.0],
            ],

            "Integer to Boolean Array Conversion" => [
                JsonSchemaTypes::Integer,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Boolean,
                [true],
            ],

            "Number to Integer Array Conversion" => [
                JsonSchemaTypes::Number,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Integer,
                [1],
            ],

            "Number to String Array Conversion" => [
                JsonSchemaTypes::Number,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::String,
                ['1'],
            ],

            "Number to Boolean Array Conversion" => [
                JsonSchemaTypes::Number,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Boolean,
                [true],
            ],

            "String to Integer Array Conversion" => [
                JsonSchemaTypes::String,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Integer,
                [1],
            ],

            "String to Number Array Conversion" => [
                JsonSchemaTypes::String,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Number,
                [1.0],
            ],

            "String to Boolean Array Conversion" => [
                JsonSchemaTypes::String,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Boolean,
                [true],
            ],

            "Boolean to String Array Conversion" => [
                JsonSchemaTypes::Boolean,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::String,
                ['1'],
            ],

            "Boolean to Integer Array Conversion" => [
                JsonSchemaTypes::Boolean,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Integer,
                [1],
            ],

            "Boolean to Number Array Conversion" => [
                JsonSchemaTypes::Boolean,
                1,
                JsonSchemaTypes::Array,
                JsonSchemaTypes::Number,
                [1.0],
            ],
        ];
    }
}
