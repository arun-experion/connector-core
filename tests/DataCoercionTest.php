<?php

use Connector\Type\DataType;
use Connector\Type\JsonSchemaTypes;
use Connector\Type\TypedValue;
use Connector\Exceptions\DataConversionException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Type\TypedValue
 * @covers \Connector\Type\DataType
 */
final class DataCoercionTest extends TestCase
{

    /**
     * @dataProvider Integers
     */
    public function testIntegerCoercion($expected, $input, $locale): void
    {
        $type       = new DataType("integer");
        $type->setLocale($locale);
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function Integers(): array
    {
        return [
            "en-US integer #1" => [2, "2", "en-US"],
            "en-US integer #2" => [2, " 2 ", "en-US"],
            "en-US integer #3" => [-2, "-2", "en-US"],
            "en-US integer #4" => [2, "+2", "en-US"],
            "fr-FR integer #1" => [-2, "-2", "fr-FR"],
            "empty string"     => [0, "", "en-US"],
            "blank string"     => [0, " ", "en-US"]
        ];
    }

    /**
     * @dataProvider Numbers
     */
    public function testNumberCoercion($expected, $input, $locale): void
    {
        $type       = new DataType("number");
        $type->setLocale($locale);
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function Numbers(): array
    {
        return [
            "fr-FR number #1" => [1200.2, "1 200,2", "fr-FR"],
            "fr-FR number #2" => [1200.2, "1200,20", "fr-FR"],
            "fr-FR number #3" => [2.0, "2", "fr-FR"],
            "de-DE number #1" => [1200.2, "1.200,20", "de-DE"],
            "de-DE number #2" => [1200.2, "1200,2", "de-DE"],
            "de-DE number #3" => [2.0, "2", "de-DE"],
            "en-US number #1" => [2.0, "2", "en-US"],
            "en-US number #2" => [2.0, " 2 ", "en-US"],
            "en-US number #3" => [-2.0, "-2", "en-US"],
            "en-US number #4" => [2.0, "+2", "en-US"],
            "empty string"    => [0.0, "", "en-US"],
            "blank string"    => [0.0, " ", "en-US"]
        ];
    }

    /**
     * @dataProvider DateValues
     */
    public function testDateCoercion($expected, $input, $locale): void
    {
        $type = new DataType("string", "date");
        $type->setLocale($locale);
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function DateValues(): array
    {
        return [
            "ISO 8601 date variation 1"  => ["2023-12-31", "2023-12-31", "en-US"],
            "de-DE date variation 1" => ["2023-12-01", "1.12.2023", "de-DE"],
            "de-DE date variation 2" => ["2023-12-01", "1.12.23", "de-DE"],
            "en-US date variation 1" => ["2023-12-31", "12/31/2023", "en-US"],
            "en-US date variation 2" => ["2023-12-31", "12.31.2023", "en-US"],
            "en-US date variation 3" => ["2023-12-31", "12-31-2023", "en-US"],
            "en-US date variation 4" => ["2023-12-31", "12/31/23", "en-US"],
            "en-US date variation 5" => ["2023-12-31", "12.31.23", "en-US"],
            "en-US date variation 6" => ["2023-12-31", "12-31-23", "en-US"],
            "fr-FR date variation 1" => ["2023-12-01", "1/12/2023", "fr-FR"],
            "fr-FR date variation 2" => ["2023-12-01", "1/12/23", "fr-FR"],
        ];
    }

    public function testIntlDateFormatterOnPlatform() {
        $this->markTestSkipped("Enable test when ICU version is upgraded to >= 72.1");
        $f = new IntlDateFormatter(
            "en-US",
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            "UTC",
            IntlDateFormatter::GREGORIAN
        );
        $f->setLenient(false);
        $f->setPattern("yyyy-MM-dd'T'HH:mm:ss.SSSXXX");
        $v = $f->parse("2023-12-31T15:30:00.996+0200");
        $this->assertEquals(1704029400, $v);
    }

    /**
     * @dataProvider DateTimeValues
     */
    public function testDateTimeCoercion($expected, $input, $locale): void
    {
        $type = new DataType("string", "date-time");
        $type->setLocale($locale);
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function DateTimeValues()
    {
        return [
            // skipped - test sensitive to ICU library version. Require ICU >= 72.1
            // "ISO 8601 datetime variation 1"  => ["2023-12-31T13:30:00+0000", "2023-12-31T15:30:00.996+0200", "en-US"],
            // skipped - test sensitive to ICU library version. Require ICU >= 72.1
            // "ISO 8601 datetime variation 2"  => ["2023-12-31T13:30:00+0000", "2023-12-31T15:30:00+0200", "en-US"],
            // skipped - test sensitive to ICU library version. Require ICU >= 72.1
            // "ISO 8601 datetime variation 3"  => ["2023-12-31T13:30:00+0000", "2023-12-31 15:30:00+0200", "en-US"],
            "ISO 8601 datetime variation 4"  => ["2023-12-31T15:30:00+0000", "2023-12-31 15:30:00", "en-US"],
            "en-US datetime variation 1"  => ["2023-12-31T15:30:00+0000", "12/31/2023 3:30pm",   "en-US"],
            "en-US datetime variation 2"  => ["2023-12-31T15:30:00+0000", "12.31.2023 3:30pm",   "en-US"],
            "en-US datetime variation 3"  => ["2023-12-31T15:30:00+0000", "12-31-2023 3:30pm",   "en-US"],
            "en-US datetime variation 4"  => ["2023-12-31T15:30:00+0000", "12-31-2023 03:30pm",  "en-US"],
            "en-US datetime variation 5"  => ["2023-12-31T15:00:00+0000", "12-31-2023 3pm",      "en-US"],
            "en-US datetime variation 6"  => ["2023-12-31T03:30:00+0000", "12-31-2023 3:30am",   "en-US"],
            "en-US datetime variation 7"  => ["2023-12-31T03:30:00+0000", "12-31-23 3:30am",     "en-US"],
            "en-US datetime variation 8"  => ["2023-12-31T03:30:00+0000", "12/31/23 3:30 am",    "en-US"],
            "en-US datetime variation 9"  => ["2023-12-31T03:30:00+0000", "12/31/23 3:30 Am",    "en-US"],
            "en-US datetime variation 10" => ["2023-12-31T03:30:20+0000", "12/31/2023 3:30:20am","en-US"],
            "en-US datetime variation 11" => ["2023-12-31T03:30:10+0000", "12/31/23 03:30:10 AM","en-US"],
            "en-US datetime variation 12" => ["2023-12-31T15:30:00+0000", "12/31/23 03:30pm EST","en-US"],
            "de-DE datetime variation 1"  => ["2023-12-31T15:30:00+0000", "31.12.2023 15:30",    "de-DE"],
            "de-DE datetime variation 2"  => ["2023-12-31T15:30:00+0000", "31.12.2023 15.30",    "de-DE"],
            "de-DE datetime variation 3 (ignores seconds)"  => ["2023-12-31T15:30:00+0000", "31.12.2023 15.30.10", "de-DE"],
            "fr-FR datetime variation 1"  => ["2023-12-31T15:30:00+0000", "31/12/2023 15:30",    "fr-FR"],
            "fr-FR datetime variation 2 (ignores seconds)"  => ["2023-12-31T15:30:00+0000", "31/12/2023 15:30:10", "fr-FR"],
        ];
    }

    /**
     * @dataProvider TimeZoneValues
     */
    public function testTimeZoneCoercion($expected, $input, $timeZone): void
    {
        $type = new DataType("string","date-time");
        $type->setTimeZone($timeZone);
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function TimeZoneValues()
    {
        return [
            "Time-zone #1" => ["2023-12-31T20:30:00+0000", "12/31/2023 3:30pm", "America/Indianapolis"],
            "Time-zone #2" => ["2024-01-01T03:30:00+0000", "12/31/2023 10:30pm", "America/Indianapolis"],
        ];
    }

    /**
     * @dataProvider Booleans
     */
    public function testBooleanCoercion($expected, $input): void
    {
        $type       = new DataType("boolean");
        $typedValue = new TypedValue($input, $type);
        $this->assertSame($expected, $typedValue->value);
    }

    public static function Booleans(): array
    {
        return [
            "Boolean variation 1"  => [true, "1"],
            "Boolean variation 2"  => [true, "true"],
            "Boolean variation 3"  => [true, "TrUe"],
            "Boolean variation 4"  => [true, " 1 "],
            "Boolean variation 5"  => [false, "0"],
            "Boolean variation 6"  => [false, ""],
            "Boolean variation 7"  => [false, "false"],
            "Boolean variation 8"  => [false, "fALsE"],
            "Boolean variation 9"  => [false, " false "],
            "Boolean variation 10" => [false, " 0 "],
            "Boolean variation 11" => [true, "+1"],
            "Number 0"             => [false, 0],
            "Number 1"             => [true, 1],
            "Number 2"             => [true, 2],
            "Number -1"            => [true, -1],
            "Empty string"         => [false, ""],
            "Blank string"         => [false, " "],
        ];
    }

    /**
     * @dataProvider InvalidBooleans
     */
    public function testInvalidBooleanCoercion($input): void
    {
        $type       = new DataType("boolean");
        $this->expectException(DataConversionException::class);
        $typedValue = new TypedValue($input, $type);
    }

    public static function InvalidBooleans(): array
    {
        return [
            "Invalid Boolean #1"   => ["something"],
            "Invalid Boolean #2"   => ["-1"],
            "Invalid Boolean #3"   => ["2"],
        ];
    }


    public function testStringMaxLength(): void
    {
        $type = new DataType(JsonSchemaTypes::String);
        $type->setMaxLength(2);
        $typedValue = new TypedValue("abcdefg", $type);
        $this->assertSame("ab", $typedValue->value);

        $type = new DataType(JsonSchemaTypes::String);
        $type->setMaxLength(0);
        $typedValue = new TypedValue("abcdefg", $type);
        $this->assertSame("abcdefg", $typedValue->value, "max length of 0 means no max length");
    }
}
