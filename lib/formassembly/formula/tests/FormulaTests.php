<?php

declare(strict_types=1);

namespace Tests\Unit\Formula\Traits;

use FormAssembly\Formula\Exceptions\FormulaNotFoundException;
use FormAssembly\Formula\Exceptions\FormulaSyntaxErrorException;
use DateTime;
use DateTimeZone;
use FormAssembly\Formula\Processors\PhpSpreadsheetProcessor;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers PhpSpreadsheetProcessor
 */
final class FormulaTests extends TestCase
{
    static public function tokenizerDataProvider(): array
    {
        return [
            "Separates formula from other content"           => ["HI @LEFT(\"abc\",2) mixed", ["HI ", "@LEFT(\"abc\",2)", " mixed"]],
            "Handles nested functions as one token"         => ["H @LEFT(\"abc\",@TRUE()) mixed", ["H ", "@LEFT(\"abc\",@TRUE())", " mixed"]],
            "Handles multiple functions as separate tokens" => ["@LEFT(\"a)bc\",@TRUE()) mixed @TRIM(\" abc \")",["@LEFT(\"a)bc\",@TRUE())", " mixed ", "@TRIM(\" abc \")" ]],
            "Handles arguments with quotes and parenthesis" => ["@LEFT(\"a\\\")bc\",1)", ["@LEFT(\"a\\\")bc\",1)" ]],
            "Handles single-quoted arguments"               => ["@LEFT('a)bc',1)", ["@LEFT('a)bc',1)" ]],
            "Handles single-quoted arguments with quotes and parenthesis" => ["@LEFT('a\')bc',1)", ["@LEFT('a\')bc',1)" ]],
            "Handles formula with no other content"         => ["@LEFT(\"abc\",2)",["@LEFT(\"abc\",2)" ]],
            "Handles spaces between arguments"              => ['@DATEDIF( @DATE(2021, 11, 1), ""), "Y")', ['@DATEDIF( @DATE(2021, 11, 1), "")', ', "Y")']],
            "Handles operation as argument"                 => ['@IF("1"=“1“,1,0)', ['@IF("1"=“1“,1,0)']],
            "Handles empty string as argument"              => ['@IF("",""FALSE")', ['@IF("",""FALSE")']],
            "Handles operation with aliases"                => ['@IF(@LEN(%%tfa_1%%) < 5,true,false)', ['@IF(@LEN(%%tfa_1%%) < 5,true,false)']],
            "Handles invalid function as argument"          => ['@IF(@LEN (%%tfa_1%%) < 5,true,false)', ['@IF(@LEN (%%tfa_1%%)',' < 5,true,false)']],
        ];
    }
    /**
     * Tokenizer extracts formulas from mixed content, to be later evaluated.
    * @dataProvider tokenizerDataProvider
    */
    public function testTokenizer($expression, $expectedResult)
    {
        $processor = new PhpSpreadsheetProcessor();
        $tokens    = $processor->tokenize($expression);
        $this->assertEquals($expectedResult, $tokens);
    }

    /**
     * @param mixed $expression
     * @param mixed $expectedResult
     *
     * @dataProvider customizedFormulaDataProvider
     */
    public function testCustomizedFunctions($expression, $expectedResult): void
    {
        $processor = new PhpSpreadsheetProcessor();

        self::assertSame( $expectedResult,
            $processor->evaluate($expression),
            "Invalid output for formula: " . $expression
        );
    }

    static public function customizedFormulaDataProvider(): array
    {
        return [
            'ADDSLASHES' => ['@ADDSLASHES("Is your name O\'Reilly?")', "Is your name O\'Reilly?"],
            'ADDSLASHES - Null' => ['@ADDSLASHES(null)', ""],
            'ADDSLASHES - Array' => ['@ADDSLASHES([])', ""],
            'ADDSLASHES - Empty' => ['@ADDSLASHES("")', ""],
            'ADDSLASHES - Excel Array' => ['@ADDSLASHES({"test", "test2"})', "test2"],
            'ADDSLASHES - Number' => ['@ADDSLASHES(123)', "123"],

            'CONTAINS - offset greater than length' => ['@CONTAINS("test", "this is a test phrase", 30)', ''],
            'CONTAINS - true' => ['@CONTAINS("test", "this is a test phrase")', '1'],
            'CONTAINS - false' => ['@CONTAINS("boat", "this is a test phrase")', ''],
            'CONTAINS - same' => ['@CONTAINS("same", "same")', '1'],
            'CONTAINS - Null' => ['@CONTAINS(null, "test")', ""],
            'CONTAINS - Array' => ['@CONTAINS([], "test")', ""],
            'CONTAINS - Empty' => ['@CONTAINS("", "test")', ""],
            'CONTAINS - Excel Array' => ['@CONTAINS({"test", "test2"}, "test")', ""],
            'CONTAINS - Number' => ['@CONTAINS(123, "test")', ""],
            'CONTAINS - Null on 2nd' => ['@CONTAINS("test", null)', ""],
            'CONTAINS - Array on 2nd' => ['@CONTAINS("test", [])', ""],
            'CONTAINS - Empty on 2nd' => ['@CONTAINS("test", "")', ""],
            'CONTAINS - Excel Array on 2nd' => ['@CONTAINS("test", {"test", "test2"})', "1"],
            'CONTAINS - Number on 2nd' => ['@CONTAINS("test", 123)', ""],
            'CONTAINS - Both Array' => ['@CONTAINS([], [])', ""],
            'CONTAINS - Both null' => ['@CONTAINS(null, null)', ""],
            'CONTAINS - Both empty' => ['@CONTAINS("", "")', ""],
            'CONTAINS - Both numbers' => ['@CONTAINS(123, 123)', "1"],

            'LEN - empty string' => ['@LEN("")', '0'],
            'LEN - non-empty string' => ['@LEN("this phrase has a length of 30")', '30'],
            'LEN - emoji' => ['@LEN("⭐️")', '2'],
            'LEN - Null' => ['@LEN(null)', "0"],
            'LEN - Array' => ['@LEN([])', "0"],
            'LEN - Excel Array' => ['@LEN({"test", "test2"})', "5"],
            'LEN - Number' => ['@LEN(123)', "3"],

            'LOWER - normal' => ['@LOWER("TEst PHRASe")', 'test phrase'],
            'LOWER - mb' => ['@LOWER("mĄkA")', 'mĄka'],
            'LOWER - Null' => ['@LOWER(null)', ""],
            'LOWER - Array' => ['@LOWER([])', ""],
            'LOWER - Empty' => ['@LOWER("")', ""],
            'LOWER - Excel Array' => ['@LOWER({"TEst", "TEst2"})', "test2"],
            'LOWER - Number' => ['@LOWER(123)', "123"],

            'PROPER - word' => ['@PROPER("test")', 'Test'],
            'PROPER - phrase normal' => ['@PROPER("this is a test phrase")', 'This Is A Test Phrase'],
            'PROPER - phrase with quotes' => ['@PROPER("this is a \'test\' phrase")', 'This Is A \'Test\' Phrase'],
            'PROPER - Null' => ['@PROPER(null)', ""],
            'PROPER - Array' => ['@PROPER([])', ""],
            'PROPER - Empty' => ['@PROPER("")', ""],
            'PROPER - Excel Array' => ['@PROPER({"test", "test2"})', "Test2"],
            'PROPER - Number' => ['@PROPER(123)', "123"],

            'SUBSTITUTE - 1' => ['@SUBSTITUTE("952-455-7865","-","")', '9524557865'],
            'SUBSTITUTE - 2' => ['@SUBSTITUTE("Alphabet soup", "bet", "con")', 'Alphacon soup'],
            'SUBSTITUTE - Null' => ['@SUBSTITUTE(null, "test", "")', ""],
            'SUBSTITUTE - Array' => ['@SUBSTITUTE([], "test", "")', ""],
            'SUBSTITUTE - Empty' => ['@SUBSTITUTE("", "test", "")', ""],
            'SUBSTITUTE - Excel Array' => ['@SUBSTITUTE({"test", "test2"}, "test", "")', '2'],
            'SUBSTITUTE - Number' => ['@SUBSTITUTE(123, "test", "")', "123"],
            'SUBSTITUTE - Null on 2nd' => ['@SUBSTITUTE("test", null, "")', "test"],
            'SUBSTITUTE - Array on 2nd' => ['@SUBSTITUTE("test", [], "")', "test"],
            'SUBSTITUTE - Empty on 2nd' => ['@SUBSTITUTE("test", "", "")', "test"],
            'SUBSTITUTE - Excel Array on 2nd' => ['@SUBSTITUTE("test", {"test", "test2"}, "")', "test"],
            'SUBSTITUTE - Number on 2nd' => ['@SUBSTITUTE("test", 123, "")', "test"],
            'SUBSTITUTE - Both Array' => ['@SUBSTITUTE([], [], "")', ""],
            'SUBSTITUTE - Both null' => ['@SUBSTITUTE(null, null, "")', ""],
            'SUBSTITUTE - Both empty' => ['@SUBSTITUTE("", "", "")', ""],
            'SUBSTITUTE - Both numbers' => ['@SUBSTITUTE(123, 123, "")', ""],

            'TEXT - simple' => ['@TEXT(1)', '1'],
            'TEXT - float with preceding 0' => ['@TEXT(0.112)', '0.112'],
            'TEXT - float with trailing 0' => ['@TEXT(1.0000)', '1'],
            'TEXT - Null' => ['@TEXT(null)', ""],
            'TEXT - Array' => ['@TEXT([])', ""],
            'TEXT - Empty' => ['@TEXT("")', ""],
            'TEXT - Excel Array' => ['@TEXT({"test", "test2"})', "test2"],
            'TEXT - Number' => ['@TEXT(123)', "123"],
            'TEXT - String' => ['@TEXT("test123")', "test123"],
            'TEXT - empty string' => ['@TEXT("")', ""],

            'UPPER - normal' => ['@UPPER("upperCase me")', 'UPPERCASE ME'],
            'UPPER - mb' => ['@UPPER("Léa")', 'LéA'],
            'UPPER - Null' => ['@UPPER(null)', ""],
            'UPPER - Array' => ['@UPPER([])', ""],
            'UPPER - Empty' => ['@UPPER("")', ""],
            'UPPER - Excel Array' => ['@UPPER({"test", "test2"})', "TEST2"],
            'UPPER - Number' => ['@UPPER(123)', "123"],
            'UPPER - String' => ['@UPPER("test123")', "TEST123"],
            'UPPER - empty string' => ['@UPPER("")', ""],

            'URLENCODE' => ['@URLENCODE("encode this for URL!")', 'encode%20this%20for%20URL%21'],
            'URLDECODE' => ['@URLDECODE("encode%20this%20for%20URL%21")', 'encode this for URL!'],
            'URLENCODE - Null' => ['@URLENCODE(null)', ""],
            'URLENCODE - Array' => ['@URLENCODE([])', ""],
            'URLENCODE - Empty' => ['@URLENCODE("")', ""],
            'URLENCODE - Excel Array' => ['@URLENCODE({"test", "test2"})', "test2"],
            'URLENCODE - Number' => ['@URLENCODE(123)', "123"],
            'URLENCODE - Filled array' => ['@URLENCODE(["test1", "test2", "test3"])', "#N/A"],


            'OR - numeric string 1' => ['@OR("1")', '1'],
            'OR - numeric string 0' => ['@OR("0")', ''],
            'OR - numeric 1' => ['@OR(1)', '1'],
            'OR - numeric 0' => ['@OR(0)', ''],
            'OR - numeric 5' => ['@OR(5)', '1'],
            'OR - true' => ['@OR(true)', '1'],
            'OR - true string' => ['@OR("true")', '1'],
            'OR - false' => ['@OR(false)', ''],
            'OR - false string' => ['@OR("false")', ''],
            'OR - string' => ['@OR("test")', '#VALUE!'],
            'OR - Null' => ['@OR(null)', ""],
            'OR - Array' => ['@OR([])', ""],
            'OR - Empty' => ['@OR("")', "#VALUE!"],
            'OR - Excel Array' => ['@OR({"test", "test2"})', "#VALUE!"],
            'OR - Number' => ['@OR(123)', "1"],
            'OR - Filled array' => ['@OR(["test1", "test2", "test3"])', "#N/A"],

            'AND - numeric string true' => ['@AND("1", "1")', '1'],
            'AND - numeric string false' => ['@AND("1", "0")', ''],
            'AND - mix' => ['@AND("1", 0, "1")', ''],
            'AND - true' => ['@AND(true)', '1'],
            'AND - true string' => ['@AND("true")', '1'],
            'AND - false' => ['@AND(false)', ''],
            'AND - false string' => ['@AND("false")', ''],
            'AND - string' => ['@AND("test")', '#VALUE!'],
            'AND - Null' => ['@AND(null)', ""],
            'AND - Array' => ['@AND([])', ""],
            'AND - Empty' => ['@AND("")', "#VALUE!"],
            'AND - Excel Array' => ['@AND({"test", "test2"})', "#VALUE!"],
            'AND - Number' => ['@AND(123)', "1"],
            'AND - Filled array' => ['@AND(["test1", "test2", "test3"])', "#N/A"],

            'CONCAT - big int numbers' => [
                '@CONCATENATE("",9123456789012345,-9123456789012345,51)',
                '9123456789012345-912345678901234551',
            ],
            'CONCAT - strings with trailing zeros' => [
                '@CONCATENATE("00001234", "1234567890123456", "12345.67890")',
                '00001234123456789012345612345.67890',
            ],
            'CONCAT - Null' => ['@CONCATENATE(null, "", "123", "test")', "123test"],
            'CONCAT - Array' => ['@CONCATENATE([], "test")', "test"],
            'CONCAT - Empty' => ['@CONCATENATE("", 123)', "123"],
            'CONCAT - Excel Array' => ['@CONCATENATE({"test", "test2"}, 123, "", "test")', "testtest2123test"],
            'CONCAT - Number' => ['@CONCATENATE(123, 0, "test")', "1230test"],
            'CONCAT - Emoji' => ['@CONCATENATE("⭐", "",  123, "test")', "⭐123test"],
            'CONCAT - Filled array' => ['@CONCATENATE(["test1", "test2", "test3"], "test")', "#N/A"],
            'CONCAT - random' => ['@CONCATENATE(["test1", "test2", "test3"], [], null, false, true)', "#N/A"],
            'CONCAT - random 2' => ['@CONCATENATE(null, false, true, "test", 123)', "FALSETRUEtest123"],

            'SUM - numbers' => ['@SUM(5.5, 2.2, 3.7)', '11.4'],
            'SUM - numbers and numeric strings' => ['@SUM(2.3, "4", 9)', '15.3'],
            'SUM - numbers, strings and numeric strings' => ['@SUM(2.3, "test", 9)', '11.3'],
            'SUM - Null' => ['@SUM(null, 2, 3)', "5"],
            'SUM - Array' => ['@SUM([], 2, 3)', "5"],
            'SUM - Empty' => ['@SUM("", 2, 3)', "5"],
            'SUM - Excel Array' => ['@SUM({"test", "test2"}, 2, 3)', "5"],
            'SUM - Number' => ['@SUM(123, 2, 3)', "128"],
            'SUM - Emoji' => ['@SUM("⭐", 2, 3)', "5"],
            'SUM - Filled array' => ['@SUM(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'PRODUCT - numbers' => ['@PRODUCT(5, 2)', '10'],
            'PRODUCT - numbers and numeric strings' => ['@PRODUCT(3, "3")', '9'],
            'PRODUCT - numbers, strings and numeric strings' => ['@PRODUCT(3, "test", "2")', '6'],
            'PRODUCT - Null' => ['@PRODUCT(null, 2, 3)', "0"],
            'PRODUCT - Array' => ['@PRODUCT([], 2, 3)', "0"],
            'PRODUCT - Empty' => ['@PRODUCT("", 2, 3)', "6"],
            'PRODUCT - Excel Array' => ['@PRODUCT({"test", "test2"}, 2, 3)', "6"],
            'PRODUCT - Number' => ['@PRODUCT(123, 2, 3)', "738"],
            'PRODUCT - Emoji' => ['@PRODUCT("⭐", 2, 3)', "6"],
            'PRODUCT - Filled array' => ['@PRODUCT(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'MIN - numbers' => ['@MIN(7, 8, 1, 9, -2)', '-2'],
            'MIN - numbers and numeric strings' => ['@MIN(7, "8", "15", 9, "0")', '0'],
            'MIN - numbers, strings and numeric strings' => ['@MIN(7, "test", "test-1", 9, "4")', '4'],
            'MIN - Null' => ['@MIN(null, 2, 3)', "0"],
            'MIN - Array' => ['@MIN([], 2, 3)', "0"],
            'MIN - Empty' => ['@MIN("", 2, 3)', "2"],
            'MIN - Excel Array' => ['@MIN({"test", "test2"}, 2, 3)', "2"],
            'MIN - Number' => ['@MIN(123, 2, 3)', "2"],
            'MIN - Emoji' => ['@MIN("⭐", 2, 3)', "2"],
            'MIN - Filled array' => ['@MIN(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'MAX - numbers' => ['@MAX(7, 8, 1, 9, -2)', '9'],
            'MAX - numbers and numeric strings' => ['@MAX(7, "8", "15", 9, "0")', '15'],
            'MAX - numbers, strings and numeric strings' => ['@MAX(7, "test", "test-1", 9, "4")', '9'],
            'MAX - Null' => ['@MAX(null, 2, 3)', "3"],
            'MAX - Array' => ['@MAX([], 2, 3)', "3"],
            'MAX - Empty' => ['@MAX("", 2, 3)', "3"],
            'MAX - Excel Array' => ['@MAX({"test", "test2"}, 2, 3)', "3"],
            'MAX - Number' => ['@MAX(123, 2, 3)', "123"],
            'MAX - Emoji' => ['@MAX("⭐", 2, 3)', "3"],
            'MAX - Filled array' => ['@MAX(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'SUMSQ - numbers' => ['@SUMSQ(2, 3)', '13'],
            'SUMSQ - numbers and numeric strings' => ['@SUMSQ(2, "3")', '13'],
            'SUMSQ - numbers, strings and numeric strings' => ['@SUMSQ(2, "test", "3")', '13'],
            'SUMSQ - Null' => ['@SUMSQ(null, 2, 3)', "13"],
            'SUMSQ - Array' => ['@SUMSQ([], 2, 3)', "13"],
            'SUMSQ - Empty' => ['@SUMSQ("", 2, 3)', "13"],
            'SUMSQ - Excel Array' => ['@SUMSQ({"test", "test2"}, 2, 3)', "13"],
            'SUMSQ - Number' => ['@SUMSQ(123, 2, 3)', "15142"],
            'SUMSQ - Emoji' => ['@SUMSQ("⭐", 2, 3)', "13"],
            'SUMSQ - Filled array' => ['@SUMSQ(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'SMALL - 1st smallest' => ['@SMALL(3, 2, 7, 9, 1)', '2'],
            'SMALL - 2nd smallest' => ['@SMALL(3, 2, 7, 9, 2)', '3'],
            'SMALL - with numeric strings' => ['@SMALL("3", "2", 7, 9, 2)', '3'],
            'SMALL - with strings and numeric strings' => ['@SMALL("3", "2", "test", 9, 2)', '3'],
            'SMALL - Null' => ['@SMALL(null, 2, 3)', "#NUM!"],
            'SMALL - Array' => ['@SMALL([], 2, 3)', "#NUM!"],
            'SMALL - Empty' => ['@SMALL("", 2, 3)', "#NUM!"],
            'SMALL - Excel Array' => ['@SMALL({"test", "test2"}, 2, 3)', "#NUM!"],
            'SMALL - Number' => ['@SMALL(123, 2, 3)', "#NUM!"],
            'SMALL - Emoji' => ['@SMALL("⭐", 2, 3)', "#NUM!"],
            'SMALL - Filled array' => ['@SMALL(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'LARGE - 1st largest' => ['@LARGE(3, 2, 7, 9, 1)', '9'],
            'LARGE - 2nd largest' => ['@LARGE(3, 2, 7, 9, 2)', '7'],
            'LARGE - with numeric strings' => ['@LARGE("3", "2", 7, 9, 3)', '3'],
            'LARGE - with strings and numeric strings' => ['@LARGE("3", "2", "test", 9, 1)', '9'],
            'LARGE - Null' => ['@LARGE(null, 2, 3)', "#NUM!"],
            'LARGE - Array' => ['@LARGE([], 2, 3)', "#NUM!"],
            'LARGE - Empty' => ['@LARGE("", 2, 3)', "#NUM!"],
            'LARGE - Excel Array' => ['@LARGE({"test", "test2"}, 2, 3)', "#NUM!"],
            'LARGE - Number' => ['@LARGE(123, 2, 3)', "#NUM!"],
            'LARGE - Emoji' => ['@LARGE("⭐", 2, 3)', "#NUM!"],
            'LARGE - Filled array' => ['@LARGE(["test1", "test2", "test3"], 2, 3)', "#N/A"],

            'PERCENTILE' => ['@PERCENTILE("90", 85, "test", 65, 72, 82, 96, 70, 79, 68, 84, 0.7)', '84.3'],
            'PERCENTILE - Null' => ['@PERCENTILE(null, 100, 50)', "#NUM!"],
            'PERCENTILE - Array' => ['@PERCENTILE([], 100, 50)', "#NUM!"],
            'PERCENTILE - Empty' => ['@PERCENTILE("", 100, 50)', "#NUM!"],
            'PERCENTILE - Excel Array' => ['@PERCENTILE({"test", "test2"}, 100, 50)', "#NUM!"],
            'PERCENTILE - Number' => ['@PERCENTILE(100, 50, 0)', "50"],
            'PERCENTILE - Emoji' => ['@PERCENTILE("⭐", 100, 50)', "#NUM!"],
            'PERCENTILE - Filled array' => ['@PERCENTILE(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'QUARTILE' => ['@QUARTILE(10, 12, "test", 20, 25, "28", 30, 34, 60, 1)', '18'],
            'QUARTILE - Null' => ['@QUARTILE(null, 100, 50)', "#NUM!"],
            'QUARTILE - Array' => ['@QUARTILE([], 100, 50)', "#NUM!"],
            'QUARTILE - Empty' => ['@QUARTILE("", 100, 50)', "#NUM!"],
            'QUARTILE - Excel Array' => ['@QUARTILE({"test", "test2"}, 100, 50)', "#NUM!"],
            'QUARTILE - Number' => ['@QUARTILE(100, 50, 0)', "50"],
            'QUARTILE - Emoji' => ['@QUARTILE("⭐", 100, 50)', "#NUM!"],
            'QUARTILE - Filled array' => ['@QUARTILE(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'COUNT' => ['@COUNT(1, true, "true", "12/8/08", "19", 22.24, "1.3")', '5'],
            'COUNT - Null' => ['@COUNT(null, 100, 50)', "3"],
            'COUNT - Array' => ['@COUNT([], 100, 50)', "3"],
            'COUNT - Empty' => ['@COUNT("", 100, 50)', "2"],
            'COUNT - Excel Array' => ['@COUNT({"test", "test2"}, 100, 50)', "2"],
            'COUNT - Number' => ['@COUNT(100, 50, 0)', "3"],
            'COUNT - Number and string' => ['@COUNT(100, 50, "test")', "2"],
            'COUNT - Emoji' => ['@COUNT("⭐", 100, 50)', "2"],
            'COUNT - Filled array' => ['@COUNT(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'AVERAGE' => ['@AVERAGE(2, 3, "test", 3, "5", 7, 10)', '5'],
            'AVERAGE - Null' => ['@AVERAGE(null, 100, 50)', "50"],
            'AVERAGE - Array' => ['@AVERAGE([], 100, 50)', "50"],
            'AVERAGE - Empty' => ['@AVERAGE("", 100, 50)', "75"],
            'AVERAGE - Excel Array' => ['@AVERAGE({"test", "test2"}, 100, 50)', "75"],
            'AVERAGE - Number' => ['@AVERAGE(100, 50, 0)', "50"],
            'AVERAGE - Number and string' => ['@AVERAGE(100, 50, "test")', "75"],
            'AVERAGE - Emoji' => ['@AVERAGE("⭐", 100, 50)', "75"],
            'AVERAGE - Filled array' => ['@AVERAGE(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'MEDIAN' => ['@MEDIAN(2, 3, "test", 3, "5", 7, 10)', '4'],
            'MEDIAN - Null' => ['@MEDIAN(null, 100, 50)', "50"],
            'MEDIAN - Array' => ['@MEDIAN([], 100, 50)', "50"],
            'MEDIAN - Empty' => ['@MEDIAN("", 100, 50)', "75"],
            'MEDIAN - Number and string' => ['@MEDIAN(100, 50, "test")', "75"],
            'MEDIAN - Excel Array' => ['@MEDIAN({"test", "test2"}, 100, 50)', "75"],
            'MEDIAN - Number' => ['@MEDIAN(100, 50, 0)', "50"],
            'MEDIAN - Emoji' => ['@MEDIAN("⭐", 100, 50)', "75"],
            'MEDIAN - Filled array' => ['@MEDIAN(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'MODE' => ['@MODE(2, 3, "test", "3", "5", 7, 10)', '3'],
            'MODE - Null' => ['@MODE(null, 100, 50)', "#N/A"],
            'MODE - Array' => ['@MODE([], 100, 50)', "#N/A"],
            'MODE - Empty' => ['@MODE("", 100, 50)', "#N/A"],
            'MODE - Number and string' => ['@MODE(100, 50, "test")', "#N/A"],
            'MODE - Excel Array' => ['@MODE({"test", "test2"}, 100, 50)', "#N/A"],
            'MODE - Number' => ['@MODE(100, 50, 0)', "#N/A"],
            'MODE - Emoji' => ['@MODE("⭐", 100, 50)', "#N/A"],
            'MODE - Filled array' => ['@MODE(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'DEVSQ' => ['@DEVSQ(4, 5, 8, "test", 7, 11, "4", 3)', '48'],
            'DEVSQ - Null' => ['@DEVSQ(null, 100, 50)', "5000"],
            'DEVSQ - Array' => ['@DEVSQ([], 100, 50)', "5000"],
            'DEVSQ - Empty' => ['@DEVSQ("", 100, 50)', "1250"],
            'DEVSQ - Number and string' => ['@DEVSQ(100, 50, "test")', "1250"],
            'DEVSQ - Excel Array' => ['@DEVSQ({"test", "test2"}, 100, 50)', "1250"],
            'DEVSQ - Number' => ['@DEVSQ(100, 50, 0)', "5000"],
            'DEVSQ - Emoji' => ['@DEVSQ("⭐", 100, 50)', "1250"],
            'DEVSQ - Filled array' => ['@DEVSQ(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'AVEDEV' => ['@AVEDEV(50, 47, "52", 46, "test", 45, 48)', '2'],
            'AVEDEV - Null' => ['@AVEDEV(null, 100, 50)', "33.333333333333"],
            'AVEDEV - Array' => ['@AVEDEV([], 100, 50)', "33.333333333333"],
            'AVEDEV - Empty' => ['@AVEDEV("", 100, 50)', "25"],
            'AVEDEV - Number and string' => ['@AVEDEV(100, 50, "test")', "25"],
            'AVEDEV - Excel Array' => ['@AVEDEV({"test", "test2"}, 100, 50)', "25"],
            'AVEDEV - Number' => ['@AVEDEV(100, 50, 0)', "33.333333333333"],
            'AVEDEV - Emoji' => ['@AVEDEV("⭐", 100, 50)', "25"],
            'AVEDEV - Filled array' => ['@AVEDEV(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'HARMEAN' => ['@HARMEAN(4, 5, 8, "7", "test", 11, 4, 3)', '5.0283759620617'],
            'HARMEAN - Null' => ['@HARMEAN(null, 100, 50)', "#NUM!"],
            'HARMEAN - Array' => ['@HARMEAN([], 100, 50)', "#NUM!"],
            'HARMEAN - Empty' => ['@HARMEAN("", 100, 50)', "66.666666666667"],
            'HARMEAN - Number and string' => ['@HARMEAN(100, 50, "test")', "66.666666666667"],
            'HARMEAN - Excel Array' => ['@HARMEAN({"test", "test2"}, 100, 50)', "66.666666666667"],
            'HARMEAN - Number' => ['@HARMEAN(100, 50, 0)', "#NUM!"],
            'HARMEAN - Emoji' => ['@HARMEAN("⭐", 100, 50)', "66.666666666667"],
            'HARMEAN - Filled array' => ['@HARMEAN(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'TRIMMEAN' => ['@TRIMMEAN(4, "5", 6, 7, 2, 3, 4, 5, 1, 2, 3, "test", 0.2)', '3.7777777777778'],
            'TRIMMEAN - Null' => ['@TRIMMEAN(null, 100, 50)', "#NUM!"],
            'TRIMMEAN - Array' => ['@TRIMMEAN([], 100, 50)', "#NUM!"],
            'TRIMMEAN - Empty' => ['@TRIMMEAN("", 100, 50)', "#NUM!"],
            'TRIMMEAN - Number and string' => ['@TRIMMEAN(100, 50, "test")', "#NUM!"],
            'TRIMMEAN - Excel Array' => ['@TRIMMEAN({"test", "test2"}, 100, 50)', "#NUM!"],
            'TRIMMEAN - Number' => ['@TRIMMEAN(100, 50, 0)', "75"],
            'TRIMMEAN - Emoji' => ['@TRIMMEAN("⭐", 100, 50)', "#NUM!"],
            'TRIMMEAN - Filled array' => ['@TRIMMEAN(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'STDEV' => ['@STDEV(102, 98, 97, "100", "test", 105, 100, 95, 94, 93, 96)', '3.7712361663283'],
            'STDEV - Null' => ['@STDEV(null, 100, 50)', "50"],
            'STDEV - Array' => ['@STDEV([], 100, 50)', "50"],
            'STDEV - Empty' => ['@STDEV("", 100, 50)', "35.355339059327"],
            'STDEV - Number and string' => ['@STDEV(100, 50, "test")', "35.355339059327"],
            'STDEV - Excel Array' => ['@STDEV({"test", "test2"}, 100, 50)', "35.355339059327"],
            'STDEV - Number' => ['@STDEV(100, 50, 0)', "50"],
            'STDEV - Emoji' => ['@STDEV("⭐", 100, 50)', "35.355339059327"],
            'STDEV - Filled array' => ['@STDEV(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'STDEVP' => ['@STDEVP(1345, "1301", 1368, 1322, "test", 1310, 1370, 1318, 1350, 1303, 1299)', '26.054558142482'],
            'STDEVP - Null' => ['@STDEVP(null, 100, 50)', "40.824829046386"],
            'STDEVP - Array' => ['@STDEVP([], 100, 50)', "40.824829046386"],
            'STDEVP - Empty' => ['@STDEVP("", 100, 50)', "25"],
            'STDEVP - Number and string' => ['@STDEVP(100, 50, "test")', "25"],
            'STDEVP - Excel Array' => ['@STDEVP({"test", "test2"}, 100, 50)', "25"],
            'STDEVP - Number' => ['@STDEVP(100, 50, 0)', "40.824829046386"],
            'STDEVP - Emoji' => ['@STDEVP("⭐", 100, 50)', "25"],
            'STDEVP - Filled array' => ['@STDEVP(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'VAR' => ['@VAR(1345, 1301, 1368, "1322", 1310, 1370, "test", 1318, "1350", 1303, 1299)', '754.26666666667'],
            'VAR - Null' => ['@VAR(null, 100, 50)', "2500"],
            'VAR - Array' => ['@VAR([], 100, 50)', "2500"],
            'VAR - Empty' => ['@VAR("", 100, 50)', "1250"],
            'VAR - Number and string' => ['@VAR(100, 50, "test")', "1250"],
            'VAR - Excel Array' => ['@VAR({"test", "test2"}, 100, 50)', "1250"],
            'VAR - Number' => ['@VAR(100, 50, 0)', "2500"],
            'VAR - Emoji' => ['@VAR("⭐", 100, 50)', "1250"],
            'VAR - Filled array' => ['@VAR(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'VARP' => ['@VARP("1345", 1301, 1368, 1322, 1310, 1370, 1318, "test", 1350, 1303, 1299)', '678.84'],
            'VARP - Null' => ['@VARP(null, 100, 50)', "1666.6666666667"],
            'VARP - Array' => ['@VARP([], 100, 50)', "1666.6666666667"],
            'VARP - Empty' => ['@VARP("", 100, 50)', "625"],
            'VARP - Number and string' => ['@VARP(100, 50, "test")', "625"],
            'VARP - Excel Array' => ['@VARP({"test", "test2"}, 100, 50)', "625"],
            'VARP - Number' => ['@VARP(100, 50, 0)', "1666.6666666667"],
            'VARP - Emoji' => ['@VARP("⭐", 100, 50)', "625"],
            'VARP - Filled array' => ['@VARP(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'SUBTOTAL' => ['@SUBTOTAL("1", "120", "true", "10", 150, 23)', '75.75'],
            'SUBTOTAL - Null' => ['@SUBTOTAL(null, 100, 50)', "#VALUE!"],
            'SUBTOTAL - Array' => ['@SUBTOTAL([], 100, 50)', "#VALUE!"],
            'SUBTOTAL - Empty' => ['@SUBTOTAL("", 100, 50)', "#VALUE!"],
            'SUBTOTAL - Number and string' => ['@SUBTOTAL(100, 50, "test")', "#VALUE!"],
            'SUBTOTAL - Excel Array' => ['@SUBTOTAL({"test", "test2"}, 100, 50)', "#VALUE!"],
            'SUBTOTAL - Number' => ['@SUBTOTAL(100, 50, 0)', "#VALUE!"],
            'SUBTOTAL - Emoji' => ['@SUBTOTAL("⭐", 100, 50)', "#VALUE!"],
            'SUBTOTAL - Filled array' => ['@SUBTOTAL(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'SERIESSUM' => ['@SERIESSUM(0.785398163, 0, 2, 1, "-0.5", 0.041666667, -0.001388889)', '0.70710321520465'],
            'SERIESSUM - Null' => ['@SERIESSUM(null, 100, 50)', "#N/A"],
            'SERIESSUM - Array' => ['@SERIESSUM([], 100, 50)', "#N/A"],
            'SERIESSUM - Empty' => ['@SERIESSUM("", 100, 50)', "#N/A"],
            'SERIESSUM - Number and string' => ['@SERIESSUM(100, 50, "test")', "#N/A"],
            'SERIESSUM - Excel Array' => ['@SERIESSUM({"test", "test2"}, 100, 50)', "#N/A"],
            'SERIESSUM - Number' => ['@SERIESSUM(100, 50, 0)', "#N/A"],
            'SERIESSUM - Emoji' => ['@SERIESSUM("⭐", 100, 50)', "#N/A"],
            'SERIESSUM - Filled array' => ['@SERIESSUM(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'SKEW' => ['@SKEW(3, 4, "5", 2, "false", 3, 4, 5, 6, 4, 7)', '0.3595430714068'],
            'SKEW - Null' => ['@SKEW(null, 100, 50)', "0"],
            'SKEW - Array' => ['@SKEW([], 100, 50)', "0"],
            'SKEW - Empty' => ['@SKEW("", 100, 50)', "#DIV/0!"],
            'SKEW - Number and string' => ['@SKEW(100, 50, "test")', "#DIV/0!"],
            'SKEW - Excel Array' => ['@SKEW({"test", "test2"}, 100, 50)', "#DIV/0!"],
            'SKEW - Number' => ['@SKEW(100, 50, 0)', "0"],
            'SKEW - Emoji' => ['@SKEW("⭐", 100, 50)', "#DIV/0!"],
            'SKEW - Filled array' => ['@SKEW(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'KURT' => ['@KURT(3, "four", "4", 5, 2, 3, "4", 5, 6, 4, 7)', '-0.15179963720842'],
            'KURT - Null' => ['@KURT(null, 100, 50)', "#DIV/0!"],
            'KURT - Array' => ['@KURT([], 100, 50)', "#DIV/0!"],
            'KURT - Empty' => ['@KURT("", 100, 50)', "#DIV/0!"],
            'KURT - Number and string' => ['@KURT(100, 50, "test")', "#DIV/0!"],
            'KURT - Excel Array' => ['@KURT({"test", "test2"}, 100, 50)', "#DIV/0!"],
            'KURT - Number' => ['@KURT(100, 50, 0)', "#DIV/0!"],
            'KURT - Emoji' => ['@KURT("⭐", 100, 50)', "#DIV/0!"],
            'KURT - Filled array' => ['@KURT(["test1", "test2", "test3"], 100, 50)', "#N/A"],

            'TRUNC - 1' => ['@TRUNC(3.141593, 0)', '3'],
            'TRUNC - 2' => ['@TRUNC(3.141593)', '3'],
            'TRUNC - 3' => ['@TRUNC(3.141593,2)', '3.14'],
            'TRUNC - 4' => ['@TRUNC(999.99,-1)', '990'],
            'TRUNC - 5' => ['@TRUNC(999.99,-2)', '900'],
            'TRUNC - Null' => ['@TRUNC(null)', "0"],
            'TRUNC - Array' => ['@TRUNC([])', "0"],
            'TRUNC - Empty' => ['@TRUNC("")', "0"],
            'TRUNC - Excel Array' => ['@TRUNC({"test", "test2"})', "0"],
            'TRUNC - Number' => ['@TRUNC(100)', "100"],
            'TRUNC - Emoji' => ['@TRUNC("⭐")', "0"],
            'TRUNC - Filled array' => ['@TRUNC(["test1", "test2", "test3"])', "#N/A"],
            'TRUNC - Null on 2nd' => ['@TRUNC(100, null)', "100"],
            'TRUNC - Array on 2nd' => ['@TRUNC(100, [])', "100"],
            'TRUNC - Empty on 2nd' => ['@TRUNC(100, "")', "100"],
            'TRUNC - Excel Array on 2nd' => ['@TRUNC(100, {"test", "test2"})', "100"],
            'TRUNC - Number on 2nd' => ['@TRUNC(100, 1)', "100"],
            'TRUNC - Emoji on 2nd' => ['@TRUNC(100, "⭐")', "100"],
            'TRUNC - Filled array on 2nd' => ['@TRUNC(100, ["test1", "test2", "test3"])', "#N/A"],

            'CEILING - 10' => ['@CEILING(10, 3)', '12'],
            'CEILING - 151' => ['@CEILING(151, 100)', '200'],
            'CEILING - Null' => ['@CEILING(null, 3)', "0"],
            'CEILING - Array' => ['@CEILING([], 3)', "0"],
            'CEILING - Empty' => ['@CEILING("", 3)', "#VALUE!"],
            'CEILING - Excel Array' => ['@CEILING({"test", "test2"}, 3)', "#VALUE!"],
            'CEILING - Number' => ['@CEILING(100, 3)', "102"],
            'CEILING - Emoji' => ['@CEILING("⭐", 3)', "#VALUE!"],
            'CEILING - Filled array' => ['@CEILING(["test1", "test2", "test3"], 3)', "#N/A"],
            'CEILING - Null on 2nd' => ['@CEILING(100, null)', "#N/A"],
            'CEILING - Array on 2nd' => ['@CEILING(100, [])', "#N/A"],
            'CEILING - Empty on 2nd' => ['@CEILING(100, "")', "#VALUE!"],
            'CEILING - Excel Array on 2nd' => ['@CEILING(100, {"test", "test2"})', "#VALUE!"],
            'CEILING - Emoji on 2nd' => ['@CEILING(100, "⭐")', "#VALUE!"],
            'CEILING - Filled array on 2nd' => ['@CEILING(100, ["test1", "test2", "test3"])', "#N/A"],

            'FLOOR - 10' => ['@FLOOR(10, 3)', '9'],
            'FLOOR - 151' => ['@FLOOR(151, 100)', '100'],
            'FLOOR - Null' => ['@FLOOR(null, 3)', "0"],
            'FLOOR - Array' => ['@FLOOR([], 3)', "0"],
            'FLOOR - Empty' => ['@FLOOR("", 3)', "#VALUE!"],
            'FLOOR - Excel Array' => ['@FLOOR({"test", "test2"}, 3)', "#VALUE!"],
            'FLOOR - Number' => ['@FLOOR(100, 3)', "99"],
            'FLOOR - Emoji' => ['@FLOOR("⭐", 3)', "#VALUE!"],
            'FLOOR - Filled array' => ['@FLOOR(["test1", "test2", "test3"], 3)', "#N/A"],
            'FLOOR - Null on 2nd' => ['@FLOOR(100, null)', "#N/A"],
            'FLOOR - Array on 2nd' => ['@FLOOR(100, [])', "#N/A"],
            'FLOOR - Empty on 2nd' => ['@FLOOR(100, "")', "#VALUE!"],
            'FLOOR - Excel Array on 2nd' => ['@FLOOR(100, {"test", "test2"})', "#VALUE!"],
            'FLOOR - Emoji on 2nd' => ['@FLOOR(100, "⭐")', "#VALUE!"],
            'FLOOR - Filled array on 2nd' => ['@FLOOR(100, ["test1", "test2", "test3"])', "#N/A"],

            'CHAR - A' => ['@CHAR(65)', 'A'],
            'CHAR - a' => ['@CHAR(97)', 'a'],
            'CODE - A' => ['@CODE("A")', '65'],
            'CODE - a' => ['@CODE("a")', '97'],
            'CODE - Null' => ['@CODE(null)', "#VALUE!"],
            'CODE - Array' => ['@CODE([])', "#VALUE!"],
            'CODE - Empty' => ['@CODE("")', "#VALUE!"],
            'CODE - Excel Array' => ['@CODE({"test", "test2"})', "116"],
            'CODE - Number' => ['@CODE(100)', "49"],
            'CODE - Emoji' => ['@CODE("⭐")', "11088"],
            'CODE Filled array' => ['@CODE(["test1", "test2", "test3"])', "#N/A"],

            'DATE' => ['@DATE(2021, 12, 15)', '44545'], // DATE function returns the serial number (days since 01/01/1900)
            'DATE - Null' => ['@DATE(2021, 12, null)', '44530'],
            'DATE - Array' => ['@DATE(2021, 12, [])', '44530'],
            'DATE - Empty' => ['@DATE(2021, 12, "")', '#VALUE!'],
            'DATE - Excel Array' => ['@DATE(2021, 12, {"test", "test2"})', '#VALUE!'],
            'DATE - Number' => ['@DATE(2021, 12, 100)', '44630'],
            'DATE - Emoji' => ['@DATE(2021, 12, "⭐")', '#VALUE!'],
            'DATE - Filled array' => ['@DATE(2021, 12, ["test1", "test2", "test3"])', '#N/A'],

            'DATEDIF - Days' => ['@DATEDIF( @DATE(2021, 11, 1), @DATE(2021, 12, 1), "D")', '30'], // Calculates the difference between two dates in serial number
            'DATEDIF - Months' => ['@DATEDIF( @DATE(2021, 1, 1), @DATE(2021, 12, 1), "M")', '11'],
            'DATEDIF - Years' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), "Y")', '11'],
            'DATEDIF - Null' => ['@DATEDIF( @DATE(2021, 11, 1), null, "Y")', '#NUM!'],
            'DATEDIF - Array' => ['@DATEDIF( @DATE(2021, 11, 1), [], "Y")', '#NUM!'],
            'DATEDIF - Empty' => ['@DATEDIF( @DATE(2021, 11, 1), ""), "Y")', '#VALUE!, "Y")'],
            'DATEDIF - Excel Array' => ['@DATEDIF( @DATE(2021, 11, 1), {"test", "test2"}, "Y")', '#VALUE!'],
            'DATEDIF - Number' => ['@DATEDIF( @DATE(2021, 11, 1), 100), "Y")', '#NUM!, "Y")'],
            'DATEDIF - Emoji' => ['@DATEDIF( @DATE(2021, 11, 1), "⭐"), "Y")', '#VALUE!, "Y")'],
            'DATEDIF - Filled array' => ['@DATEDIF( @DATE(2021, 11, 1), ["test1", "test2", "test3"], "Y")', '#N/A'],
            'DATEDIF - null 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), null)', '#VALUE!'],
            'DATEDIF - Array 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), [])', '#VALUE!'],
            'DATEDIF - Empty 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), "")', '#VALUE!'],
            'DATEDIF - Excel Array 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), {"test", "test2"})', '#VALUE!'],
            'DATEDIF - Number 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), 100)', '#VALUE!'],
            'DATEDIF - Emoji 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), "⭐")', '#VALUE!'],
            'DATEDIF - Filled array 3nd' => ['@DATEDIF( @DATE(2010, 11, 1), @DATE(2021, 12, 1), ["test1", "test2", "test3"])', '#N/A'],

            'DATEVALUE - Format 3' => ['@DATEVALUE("2021-12-15")', '44545'],
            'DATEVALUE - Format 4' => ['@DATEVALUE("2021-Dec-15")', '44545'],
            'DATEVALUE - Format 5' => ['@DATEVALUE("Dec 15, 2021")', '44545'],
            'DATEVALUE - Format 6' => ['@DATEVALUE("21-12-15")', '44545'],
            'DATEVALUE - Null' => ['@DATEVALUE(null)', '#VALUE!'],
            'DATEVALUE - Array' => ['@DATEVALUE([])', '#VALUE!'],
            'DATEVALUE - Empty' => ['@DATEVALUE("")', '#VALUE!'],
            'DATEVALUE - Excel Array' => ['@DATEVALUE({"test", "test2"})', '#VALUE!'],
            'DATEVALUE - Number' => ['@DATEVALUE(100)', '#VALUE!'],
            'DATEVALUE - Emoji' => ['@DATEVALUE("⭐")', '#VALUE!'],

            'DAY' => ['@DAY(@DATE(2021, 12, 15))', '15'],
            'DAY - Null' => ['@DAY(null)', '0'],
            'DAY - Array' => ['@DAY([])', '0'],
            'DAY - Empty' => ['@DAY("")', '#VALUE!'],
            'DAY - Excel Array' => ['@DAY({"test", "test2"})', '#VALUE!'],
            'DAY - Number' => ['@DAY(100)', '9'],
            'DAY - Emoji' => ['@DAY("⭐")', '#VALUE!'],
            'DAY - Filled array' => ['@DAY(["test1", "test2", "test3"])', '#N/A'],

            'EOMONTH' => ['@EOMONTH( @DATE(2021, 12, 15), 0)', '44561'],
            'EOMONTH - +1' => ['@EOMONTH( @DATE(2021, 12, 15), 1)', '44592'],
            'EOMONTH - -1' => ['@EOMONTH( @DATE(2021, 12, 15), -1)', '44530'],
            'EOMONTH - Null' => ['@EOMONTH(null)', '#N/A'],
            'EOMONTH - Array' => ['@EOMONTH([])', '#N/A'],
            'EOMONTH - Empty' => ['@EOMONTH("")', '#N/A'],
            'EOMONTH - Excel Array' => ['@EOMONTH({"test", "test2"})', '#N/A'],
            'EOMONTH - Number' => ['@EOMONTH(100)', '#N/A'],
            'EOMONTH - Emoji' => ['@EOMONTH("⭐")', '#N/A'],
            'EOMONTH - Filled array' => ['@EOMONTH(["test1", "test2", "test3"])', '#N/A'],

            'FIND - Present' => ['@FIND("A", "BBCC@@DaDAAO012")', "10"],
            'FIND - Present - start 5' => ['@FIND("A", "BBCC@@DaDAAO012", 5)', "10"],
            'FIND - Not present' => ['@FIND("Z", "BBCC@@DaDAAO012")', "#VALUE!"],
            'FIND - Null' => ['@FIND(null, "test")', "#VALUE!"],
            'FIND - Array' => ['@FIND([], "test")', "#VALUE!"],
            'FIND - Empty' => ['@FIND("", "test")', "#VALUE!"],
            'FIND - Excel Array' => ['@FIND({"test", "test2"}, "abc")', '#VALUE!'],
            'FIND - Number' => ['@FIND(123, "test")', "#VALUE!"],
            'FIND - Null on 2nd' => ['@FIND("test", null)', "#VALUE!"],
            'FIND - Array on 2nd' => ['@FIND("test", [])', "#VALUE!"],
            'FIND - Empty on 2nd' => ['@FIND("test", "")', "#VALUE!"],
            'FIND - Excel Array on 2nd' => ['@FIND("test", {"test", "test2"})', "1"],
            'FIND - Number on 2nd' => ['@FIND("test", 123)', "#VALUE!"],
            'FIND - Both Array' => ['@FIND([], [])', "#VALUE!"],
            'FIND - Both null' => ['@FIND(null, null)', "#VALUE!"],
            'FIND - Both empty' => ['@FIND("", "")', "#VALUE!"],
            'FIND - Both numbers' => ['@FIND(123, 123)', "1"],

            'FIXED' => ['@FIXED(1000)', '1,000.00'],
            'FIXED - Without decimal and comma' => ["@FIXED(1000, 0, TRUE)", "1000"],
            'FIXED - null' => ['@FIXED(null)', '0.00'],
            'FIXED - Array' => ['@FIXED([])', "0.00"],
            'FIXED - Empty' => ['@FIXED("")', "#VALUE!"],
            'FIXED - Excel Array' => ['@FIXED({"test", "test2"})', "#VALUE!"],
            'FIXED - Number' => ['@FIXED(100)', "100.00"],
            'FIXED - Emoji' => ['@FIXED("⭐")', "#VALUE!"],
            'FIXED - Filled array' => ['@FIXED(["test1", "test2", "test3"])', "#N/A"],

            'HOUR - PM' => ['@HOUR("6:30 PM")', '18'],
            'HOUR - AM' => ['@HOUR("6:30 AM")', '6'],
            'HOUR - 24HR' => ['@HOUR("17:30")', '17'],
            'HOUR - Null' => ['@HOUR(null)', "0"],
            'HOUR - Array' => ['@HOUR([])', "0"],
            'HOUR - Empty' => ['@HOUR("")', "#VALUE!"],
            'HOUR - Excel Array' => ['@HOUR({"test", "test2"})', "#VALUE!"],
            'HOUR - Number' => ['@HOUR(100)', "0"],
            'HOUR - Emoji' => ['@HOUR("⭐")', "#VALUE!"],
            'HOUR Filled array' => ['@HOUR(["test1", "test2", "test3"])', "#N/A"],

            'IFERROR - Division by Zero' => ['@IFERROR( 1/0, "Division by Zero")', 'Division by Zero'],
            'IFERROR - Find' => ['@IFERROR( @FIND("A", "BC"), "Not Found")', 'Not Found'],
            'IFERROR - Correct' => ['@IFERROR( 100/10, "Error")', '10'],
            'IFERROR - Null' => ['@IFERROR(null, "Error")', ""],
            'IFERROR - Array' => ['@IFERROR([], "Error")', ""],
            'IFERROR - Empty' => ['@IFERROR("", "Error")', ""],
            'IFERROR - Excel Array' => ['@IFERROR({"test", "test2"}, "Error")', "test2"],
            'IFERROR - Number' => ['@IFERROR(100, "Error")', "100"],
            'IFERROR - Emoji' => ['@IFERROR("⭐", "Error")', "⭐"],
            'IFERROR Filled array' => ['@IFERROR(["test1", "test2", "test3"], "Error")', "#N/A"],
            'IFERROR - Null on 2nd' => ['@IFERROR("test", null)', "test"],
            'IFERROR - Array on 2nd' => ['@IFERROR("test", [])', "test"],
            'IFERROR - Empty on 2nd' => ['@IFERROR("test", "")', "test"],
            'IFERROR - Excel Array on 2nd' => ['@IFERROR("test", {"test", "test2"})', "test"],
            'IFERROR - Number on 2nd' => ['@IFERROR("test", 100)', "test"],
            'IFERROR - Emoji on 2nd' => ['@IFERROR("test", "⭐")', "test"],
            'IFERROR Filled array on 2nd' => ['@IFERROR("test", ["test1", "test2", "test3"])', "#N/A"],

            'INT - Negative' => ['@INT(-10.8)', '-11'],
            'INT - Positive' => ['@INT(10.8)', '10'],
            'INT - Null' => ['@INT(null)', "0"],
            'INT - Array' => ['@INT([])', "0"],
            'INT - Empty' => ['@INT("")', "0"],
            'INT - string' => ['@INT("test")', "0"],
            'INT - Excel Array' => ['@INT({"test", "test2"})', "0"],
            'INT - Number' => ['@INT(100)', "100"],
            'INT - Emoji' => ['@INT("⭐")', "0"],
            'INT Filled array' => ['@INT(["test1", "test2", "test3"])', "#N/A"],

            'ISBLANK - Null' => ['@ISBLANK(null)', '1'],
            'ISBLANK - NA' => ['@ISBLANK(@NA())', ''],
            'ISBLANK - Zero' => ['@ISBLANK(0)', ''],
            'ISBLANK - Empty string' => ['@ISBLANK("")', '1'],
            'ISBLANK - Space' => ['@ISBLANK(" ")', ''],
            'ISBLANK - With string' => ['@ISBLANK("Not Blank")', ''],
            'ISBLANK - Array' => ['@ISBLANK([])', "1"],
            'ISBLANK - Excel Array' => ['@ISBLANK({"test", "test2"})', ""],
            'ISBLANK - Number' => ['@ISBLANK(100)', ""],
            'ISBLANK - Emoji' => ['@ISBLANK("⭐")', ""],
            'ISBLANK Filled array' => ['@ISBLANK(["test1", "test2", "test3"])', "#N/A"],
            'ISBLANK - No Argument' => ['@ISBLANK()', '1'],

            'ISERROR - True' => ['@ISERROR(10/0)', '1'],
            'ISERROR - False' => ['@ISERROR(10/2)', ''],
            'ISERROR - Null' => ['@ISERROR(null)', ""],
            'ISERROR - Array' => ['@ISERROR([])', ""],
            'ISERROR - Empty' => ['@ISERROR("")', ""],
            'ISERROR - Excel Array' => ['@ISERROR({"test", "test2"})', ""],
            'ISERROR - Number' => ['@ISERROR(100)', ""],
            'ISERROR - Emoji' => ['@ISERROR("⭐")', ""],
            'ISERROR Filled array' => ['@ISERROR(["test1", "test2", "test3"])', "#N/A"],

            'ISEVEN - True' => ['@ISEVEN(5)', ''],
            'ISEVEN - String with number' => ['@ISEVEN("5")', ''],
            'ISEVEN - False' => ['@ISEVEN(6)', '1'],
            'ISEVEN - String' => ['@ISEVEN("ABC")', '#VALUE!'],
            'ISEVEN - String 2' => ['@ISEVEN("ABCD")', '#VALUE!'],
            'ISEVEN - Null' => ['@ISEVEN(null)', "#NAME?"],
            'ISEVEN - Array' => ['@ISEVEN([])', "#NAME?"],
            'ISEVEN - Empty' => ['@ISEVEN("")', "#VALUE!"],
            'ISEVEN - Excel Array' => ['@ISEVEN({"test", "test2"})', "#VALUE!"],
            'ISEVEN - Number' => ['@ISEVEN(100)', "1"],
            'ISEVEN - Emoji' => ['@ISEVEN("⭐")', "#VALUE!"],
            'ISEVEN Filled array' => ['@ISEVEN(["test1", "test2", "test3"])', "#N/A"],

            'ISODD - True' => ['@ISODD(5)', '1'],
            'ISODD - String with number' => ['@ISODD("6")', ''],
            'ISODD - False' => ['@ISODD(6)', ''],
            'ISODD - String' => ['@ISODD("ABC")', '#VALUE!'],
            'ISODD - String 2' => ['@ISODD("ABCD")', '#VALUE!'],
            'ISODD - Null' => ['@ISODD(null)', "#NAME?"],
            'ISODD - Array' => ['@ISODD([])', "#NAME?"],
            'ISODD - Empty' => ['@ISODD("")', "#VALUE!"],
            'ISODD - Excel Array' => ['@ISODD({"test", "test2"})', "#VALUE!"],
            'ISODD - Number' => ['@ISODD(100)', ""],
            'ISODD - Emoji' => ['@ISODD("⭐")', "#VALUE!"],
            'ISODD Filled array' => ['@ISODD(["test1", "test2", "test3"])', "#N/A"],

            'ISNA - True' => ['@ISNA(@NA())', '1'],
            'ISNA - False' => ['@ISNA(100/2)', ''],
            'ISNA - Null' => ['@ISNA(null)', ""],
            'ISNA - Array' => ['@ISNA([])', ""],
            'ISNA - Empty' => ['@ISNA("")', ""],
            'ISNA - Excel Array' => ['@ISNA({"test", "test2"})', ""],
            'ISNA - Number' => ['@ISNA(100)', ""],
            'ISNA - Emoji' => ['@ISNA("⭐")', ""],
            'ISNA Filled array' => ['@ISNA(["test1", "test2", "test3"])', "#N/A"],

            'ISNUMBER - Number' => ['@ISNUMBER(2)', '1'],
            'ISNUMBER - Decimal' => ['@ISNUMBER(2.5)', '1'],
            'ISNUMBER - String with number' => ['@ISNUMBER("2")', '1'],
            'ISNUMBER - String' => ['@ISNUMBER("Test")', ''],
            'ISNUMBER - Calculation' => ['@ISNUMBER(10/5)', '1'],
            'ISNUMBER - PI' => ['@ISNUMBER(@PI())', '1'],
            'ISNUMBER - Null' => ['@ISNUMBER(null)', ""],
            'ISNUMBER - Array' => ['@ISNUMBER([])', ""],
            'ISNUMBER - Empty' => ['@ISNUMBER("")', ""],
            'ISNUMBER - Excel Array' => ['@ISNUMBER({"test", "test2"})', ""],
            'ISNUMBER - Emoji' => ['@ISNUMBER("⭐")', ""],
            'ISNUMBER Filled array' => ['@ISNUMBER(["test1", "test2", "test3"])', "#N/A"],

            'LEFT - Default' => ['@LEFT("ABC")', 'ABC'],
            'LEFT - 1 Char' => ['@LEFT("ABC", 1)', 'A'],
            'LEFT - 2 Chars' => ['@LEFT("ABC", 2)', 'AB'],
            'LEFT - Null' => ['@LEFT(null)', ""],
            'LEFT - Array' => ['@LEFT([])', ""],
            'LEFT - Empty' => ['@LEFT("")', ""],
            'LEFT - Excel Array' => ['@LEFT({"test", "test2"})', "test2"],
            'LEFT - Emoji' => ['@LEFT("⭐")', "⭐"],
            'LEFT Filled array' => ['@LEFT(["test1", "test2", "test3"])', "#N/A"],
            'LEFT - Null 2nd' => ['@LEFT("ABC", null)', "ABC"],
            'LEFT - Array 2nd' => ['@LEFT("ABC", [])', "ABC"],
            'LEFT - Empty 2nd' => ['@LEFT("ABC", "")', ""],
            'LEFT - Excel Array 2nd' => ['@LEFT("ABC", {"test", "test2"})', ""],
            'LEFT - Emoji 2nd' => ['@LEFT("ABC", "⭐")', ""],
            'LEFT Filled array 2nd' => ['@LEFT("ABC", ["test1", "test2", "test3"])', "#N/A"],

            'MID' => ['@MID("AABBCCDDEE", 4, 3)', 'BCC'],
            'MID - Null' => ['@MID(null, 4, 3)', ""],
            'MID - Array' => ['@MID([], 4, 3)', ""],
            'MID - Empty' => ['@MID("", 4, 3)', ""],
            'MID - Number and string' => ['@MID(4, 3, "test")', ""],
            'MID - Excel Array' => ['@MID({"test", "test2"}, 4, 3)', "t2"],
            'MID - Number' => ['@MID(100, 4, 3)', ""],
            'MID - Emoji' => ['@MID("⭐", 4, 3)', ""],
            'MID - Filled array' => ['@MID(["test1", "test2", "test3"], 4, 3)', "#N/A"],

            'MINUTE' => ['@MINUTE("6:30 PM")', '30'],
            'MINUTE - Null' => ['@MINUTE(null)', "0"],
            'MINUTE - Array' => ['@MINUTE([])', "0"],
            'MINUTE - Empty' => ['@MINUTE("")', "#VALUE!"],
            'MINUTE - Excel Array' => ['@MINUTE({"test", "test2"})', "#VALUE!"],
            'MINUTE - Number' => ['@MINUTE(100)', "0"],
            'MINUTE - Emoji' => ['@MINUTE("⭐")', "#VALUE!"],
            'MINUTE - Filled array' => ['@MINUTE(["test1", "test2", "test3"])', "#N/A"],

            'MOD' => ['@MOD(7, 3)', '1'],
            'MOD - Null' => ['@MOD(null, 3)', "0"],
            'MOD - Array' => ['@MOD([], 3)', "0"],
            'MOD - Empty' => ['@MOD("", 3)', "#VALUE!"],
            'MOD - Excel Array' => ['@MOD({"test", "test2"}, 3)', "#VALUE!"],
            'MOD - Number' => ['@MOD(100, 3)', "1"],
            'MOD - Emoji' => ['@MOD("⭐", 3)', "#VALUE!"],
            'MOD - Filled array' => ['@MOD(["test1", "test2", "test3"], 3)', "#N/A"],
            'MOD - Null 2nd' => ['@MOD(3, null)', "#DIV/0!"],
            'MOD - Array 2nd' => ['@MOD(3, [])', "#DIV/0!"],
            'MOD - Empty 2nd' => ['@MOD(3, "")', "#VALUE!"],
            'MOD - Excel Array 2nd' => ['@MOD(3, {"test", "test2"})', "#VALUE!"],
            'MOD - Number 2nd' => ['@MOD(3, 100)', "3"],
            'MOD - Emoji 2nd' => ['@MOD(3, "⭐")', "#VALUE!"],
            'MOD - Filled array 2nd' => ['@MOD(3, ["test1", "test2", "test3"])', "#N/A"],

            'MONTH - Long version' => ['@MONTH("25-December-2021")', '12'],
            'MONTH - Small version' => ['@MONTH("25-Dec-2021")', '12'],
            'MONTH - Null' => ['@MONTH(null)', "1"],
            'MONTH - Array' => ['@MONTH([])', "1"],
            'MONTH - Empty' => ['@MONTH("")', "#VALUE!"],
            'MONTH - Excel Array' => ['@MONTH({"test", "test2"})', "#VALUE!"],
            'MONTH - Number' => ['@MONTH(100)', "4"],
            'MONTH - Emoji' => ['@MONTH("⭐")', "#VALUE!"],
            'MONTH - Filled array' => ['@MONTH(["test1", "test2", "test3"])', "#N/A"],

            'NA' => ['@NA()', '#N/A'],
            'NA - Null' => ['@NA(null)', "#N/A"],
            'NA - Array' => ['@NA([])', "#N/A"],
            'NA - Empty' => ['@NA("")', "#N/A"],
            'NA - Excel Array' => ['@NA({"test", "test2"})', "#N/A"],
            'NA - Number' => ['@NA(100)', "#N/A"],
            'NA - Emoji' => ['@NA("⭐")', "#N/A"],
            'NA - Filled array' => ['@NA(["test1", "test2", "test3"])', "#N/A"],

            'NOT' => ['@NOT(true)', ''],
            'NOT - Null' => ['@NOT(null)', "1"],
            'NOT - Array' => ['@NOT([])', "1"],
            'NOT - Empty' => ['@NOT("")', "1"],
            'NOT - Excel Array' => ['@NOT({"test", "test2"})', ""],
            'NOT - Number' => ['@NOT(100)', ""],
            'NOT - Emoji' => ['@NOT("⭐")', ""],
            'NOT - Filled array' => ['@NOT(["test1", "test2", "test3"])', "#N/A"],

            'PI' => ['@PI()', '3.1415926535898'],

            'POWER' => ['@POWER(2, 3)', '8'],
            'POWER - Null' => ['@POWER(null, 2)', '0'],
            'POWER - Array' => ['@POWER([], 2)', '0'],
            'POWER - Empty' => ['@POWER("", 2)', '0'],
            'POWER - Excel Array' => ['@POWER({"test", "test2"}, 2)', '0'],
            'POWER - Emoji' => ['@POWER("⭐", 2)', '0'],
            'POWER - Filled array' => ['@POWER(["test1", "test2", "test3"], 2)', "#N/A"],
            'POWER - Null 2nd' => ['@POWER(2, null)', '1'],
            'POWER - Array 2nd' => ['@POWER(2, [])', '1'],
            'POWER - Empty 2nd' => ['@POWER(2, "")', '1'],
            'POWER - Excel Array 2nd' => ['@POWER(2, {"test", "test2"})', '1'],
            'POWER - Emoji 2nd' => ['@POWER(2, "⭐")', '1'],
            'POWER - Filled array 2nd' => ['@POWER(2, ["test1", "test2", "test3"])', "#N/A"],

            'QUOTIENT' => ['@QUOTIENT(12, 5)', '2'],
            'QUOTIENT - 100' => ['@QUOTIENT(100, 5)', '20'],
            'QUOTIENT - Null' => ['@QUOTIENT(null, 2)', '2'],
            'QUOTIENT - Array' => ['@QUOTIENT([], 2)', '2'],
            'QUOTIENT - Empty' => ['@QUOTIENT("", 2)', '2'],
            'QUOTIENT - Excel Array' => ['@QUOTIENT({"test", "test2"}, 2)', '2'],
            'QUOTIENT - Emoji' => ['@QUOTIENT("⭐", 2)', '2'],
            'QUOTIENT - Filled array' => ['@QUOTIENT(["test1", "test2", "test3"], 2)', "#N/A"],
            'QUOTIENT - Null 2nd' => ['@QUOTIENT(2, null)', '2'],
            'QUOTIENT - Array 2nd' => ['@QUOTIENT(2, [])', '2'],
            'QUOTIENT - Empty 2nd' => ['@QUOTIENT(2, "")', '2'],
            'QUOTIENT - Excel Array 2nd' => ['@QUOTIENT(2, {"test", "test2"})', '2'],
            'QUOTIENT - Emoji 2nd' => ['@QUOTIENT(2, "⭐")', '2'],
            'QUOTIENT - Filled array 2nd' => ['@QUOTIENT(2, ["test1", "test2", "test3"])', "#N/A"],

            'RAND' => ['@ISNUMBER(@RAND())', '1'],
            'RAND - Null' => ['@ISNUMBER(@RAND(null))', '1'],
            'RAND - Array' => ['@ISNUMBER(@RAND([]))', '1'],
            'RAND - Empty' => ['@ISNUMBER(@RAND(""))', '1'],
            'RAND - Excel Array' => ['@ISNUMBER(@RAND({"test", "test2"}))', '1'],
            'RAND - Number' => ['@ISNUMBER(@RAND(100))', '1'],
            'RAND - Emoji' => ['@ISNUMBER(@RAND("⭐"))', '1'],
            'RAND - Filled array' => ['@ISNUMBER(@RAND(["test1", "test2", "test3"]))', "#N/A"],

            'RANDBETWEEN' => ['@ISNUMBER(@RANDBETWEEN(1, 10))', '1'],
            'RANDBETWEEN - Null' => ['@ISNUMBER(@RANDBETWEEN(null, 10))', '1'],
            'RANDBETWEEN - Array' => ['@ISNUMBER(@RANDBETWEEN([], 10))', '1'],
            'RANDBETWEEN - Empty' => ['@ISNUMBER(@RANDBETWEEN("", 10))', '1'],
            'RANDBETWEEN - Excel Array' => ['@ISNUMBER(@RANDBETWEEN({"test", "test2", 10}))', '1'],
            'RANDBETWEEN - Number' => ['@ISNUMBER(@RANDBETWEEN(100, 10))', '1'],
            'RANDBETWEEN - Emoji' => ['@ISNUMBER(@RANDBETWEEN("⭐", 10))', '1'],
            'RANDBETWEEN - Filled array' => ['@ISNUMBER(@RANDBETWEEN(["test1", "test2", "test3"], 10))', "#N/A"],

            'REPLACE' => ['@REPLACE("XYZ123",4,3,"456")', 'XYZ456'],

            'REPT - 1 Char' => ['@REPT("*", 5)', '*****'],
            'REPT - 2 Chars' => ['@REPT("AB", 5)', 'ABABABABAB'],
            'REPT - Repeat as string number' => ['@REPT("*", "5")', '*****'],
            'REPT - Repeat number with string' => ['@REPT(5, "5")', '55555'],
            'REPT - Null' => ['@REPT("*", null)', ''],
            'REPT - Array' => ['@REPT("*", [])', ''],
            'REPT - Empty' => ['@REPT("*", "")', ''],
            'REPT - Excel Array' => ['@REPT("*", {"test", "test2"})', ''],
            'REPT - Emoji' => ['@REPT("*", "⭐")', ''],
            'REPT - Excel Array on 2nd' => ['@REPT({"test", "test2"}, 2)', 'test2test2'],

            'RIGHT' => ['@RIGHT("ABC", 2)', 'BC'],
            'RIGHT - Null' => ['@RIGHT(null)', ""],
            'RIGHT - Array' => ['@RIGHT([])', ""],
            'RIGHT - Empty' => ['@RIGHT("")', ""],
            'RIGHT - Excel Array' => ['@RIGHT({"test", "test2"})', ""],
            'RIGHT - Emoji' => ['@RIGHT("⭐")', ""],
            'RIGHT Filled array' => ['@RIGHT(["test1", "test2", "test3"])', "#N/A"],
            'RIGHT - Null 2nd' => ['@RIGHT("ABC", null)', ""],
            'RIGHT - Array 2nd' => ['@RIGHT("ABC", [])', ""],
            'RIGHT - Empty 2nd' => ['@RIGHT("ABC", "")', ""],
            'RIGHT - Excel Array 2nd' => ['@RIGHT("ABC", {"test", "test2"})', ""],
            'RIGHT - Emoji 2nd' => ['@RIGHT("ABC", "⭐")', ""],
            'RIGHT Filled array 2nd' => ['@RIGHT("ABC", ["test1", "test2", "test3"])', "#N/A"],

            'ROUND - UP' => ['@ROUND(5.789, 1)', '5.8'],
            'ROUND - Down 2 places' => ['@ROUND(5.749, 2)', '5.75'],
            'ROUND - Null' => ['@ROUND(null, 1)', '0'],
            'ROUND - Array' => ['@ROUND([], 1)', '0'],
            'ROUND - Empty' => ['@ROUND("", 1)', '0'],
            'ROUND - Excel Array' => ['@ROUND({"test", "test2"}, 1)', '0'],
            'ROUND - Number' => ['@ROUND(100, 1)', '100'],
            'ROUND - Emoji' => ['@ROUND("⭐", 1)', '0'],
            'ROUND - Filled array' => ['@ROUND(["test1", "test2", "test3"], 1)', "#N/A"],

            'ROUNDDOWN' => ['@ROUNDDOWN(5.789, 1)', '5.7'],
            'ROUNDDOWN - Null' => ['@ROUNDDOWN(null, 1)', '0'],
            'ROUNDDOWN - Array' => ['@ROUNDDOWN([], 1)', '0'],
            'ROUNDDOWN - Empty' => ['@ROUNDDOWN("", 1)', '#VALUE!'],
            'ROUNDDOWN - Excel Array' => ['@ROUNDDOWN({"test", "test2"}, 1)', '#VALUE!'],
            'ROUNDDOWN - Number' => ['@ROUNDDOWN(100, 1)', '100'],
            'ROUNDDOWN - Emoji' => ['@ROUNDDOWN("⭐", 1)', '#VALUE!'],
            'ROUNDDOWN - Filled array' => ['@ROUNDDOWN(["test1", "test2", "test3"], 1)', "#N/A"],

            'ROUNDUP' => ['@ROUNDUP(5.789, 1)', '5.8'],
            'ROUNDUP - Null' => ['@ROUNDUP(null, 1)', '0'],
            'ROUNDUP - Array' => ['@ROUNDUP([], 1)', '0'],
            'ROUNDUP - Empty' => ['@ROUNDUP("", 1)', '#VALUE!'],
            'ROUNDUP - Excel Array' => ['@ROUNDUP({"test", "test2"}, 1)', '#VALUE!'],
            'ROUNDUP - Number' => ['@ROUNDUP(100, 1)', '100'],
            'ROUNDUP - Emoji' => ['@ROUNDUP("⭐", 1)', '#VALUE!'],
            'ROUNDUP - Filled array' => ['@ROUNDUP(["test1", "test2", "test3"], 1)', "#N/A"],

            'SEARCH' => ['@SEARCH("B", "ABC")', '2'],
            'SEARCH - Null' => ['@SEARCH(null, "ABC")', '#VALUE!'],
            'SEARCH - Array' => ['@SEARCH([], "ABC")', '#VALUE!'],
            'SEARCH - Empty' => ['@SEARCH("", "ABC")', '#VALUE!'],
            'SEARCH - Excel Array' => ['@SEARCH({"test", "test2"}, "ABC")', '#VALUE!'],
            'SEARCH - Number' => ['@SEARCH(100, "ABC")', '#VALUE!'],
            'SEARCH - Emoji' => ['@SEARCH("⭐", "ABC")', '#VALUE!'],
            'SEARCH - Filled array' => ['@SEARCH(["test1", "test2", "test3"], "ABC")', '#N/A'],

            'SECOND' => ['@SECOND("Dec 25, 2021 6:30:35 PM")', '35'],
            'SECOND - Null' => ['@SECOND(null)', "0"],
            'SECOND - Array' => ['@SECOND([])', "0"],
            'SECOND - Empty' => ['@SECOND("")', "#VALUE!"],
            'SECOND - Excel Array' => ['@SECOND({"test", "test2"})', "#VALUE!"],
            'SECOND - Number' => ['@SECOND(100)', "0"],
            'SECOND - Emoji' => ['@SECOND("⭐")', "#VALUE!"],
            'SECOND - Filled array' => ['@SECOND(["test1", "test2", "test3"])', "#N/A"],

            'SQRT' => ['@SQRT(121)', '11'],
            'SQRT - Null' => ['@SQRT(null)', "0"],
            'SQRT - Array' => ['@SQRT([])', "0"],
            'SQRT - Empty' => ['@SQRT("")', "#VALUE!"],
            'SQRT - Excel Array' => ['@SQRT({"test", "test2"})', "#VALUE!"],
            'SQRT - Number' => ['@SQRT(100)', "10"],
            'SQRT - Emoji' => ['@SQRT("⭐")', "#VALUE!"],
            'SQRT - Filled array' => ['@SQRT(["test1", "test2", "test3"])', "#N/A"],

            'TIME' => ['@TIME(12, 0, 0)', '0.5'],
            'TIME - Null' => ['@TIME(null, 0, 0)', "0"],
            'TIME - Array' => ['@TIME([], 0, 0)', "0"],
            'TIME - Empty' => ['@TIME("", 0, 0)', "#VALUE!"],
            'TIME - Excel Array' => ['@TIME({"test", "test2"}, 0, 0)', "#VALUE!"],
            'TIME - Number' => ['@TIME(100, 0, 0)', "0.16666666666667"],
            'TIME - Emoji' => ['@TIME("⭐", 0, 0)', "#VALUE!"],
            'TIME - Filled array' => ['@TIME(["test1", "test2", "test3"], 0, 0)', "#N/A"],

            'TIMEVALUE' => ['@TIMEVALUE("12:00:00 PM")', '0.5'],
            'TIMEVALUE - Null' => ['@TIMEVALUE(null)', "#VALUE!"],
            'TIMEVALUE - Array' => ['@TIMEVALUE([])', "#VALUE!"],
            'TIMEVALUE - Empty' => ['@TIMEVALUE("")', "#VALUE!"],
            'TIMEVALUE - Excel Array' => ['@TIMEVALUE({"test", "test2"})', "#VALUE!"],
            'TIMEVALUE - Number' => ['@TIMEVALUE(100)', "#VALUE!"],
            'TIMEVALUE - Emoji' => ['@TIMEVALUE("⭐")', "#VALUE!"],
            'TIMEVALUE - Filled array' => ['@TIMEVALUE(["test1", "test2", "test3"])', "#N/A"],

            'TRIM' => ['@TRIM("   MY  TEST      STRING       ")', 'MY TEST STRING'],
            'TRIM - Null' => ['@TRIM(null)', ""],
            'TRIM - Array' => ['@TRIM([])', ""],
            'TRIM - Empty' => ['@TRIM("")', ""],
            'TRIM - Excel Array' => ['@TRIM({"test", "test2"})', "test2"],
            'TRIM - Number' => ['@TRIM(100)', "100"],
            'TRIM - Emoji' => ['@TRIM("⭐")', "⭐"],
            'TRIM - Filled array' => ['@TRIM(["test1", "test2", "test3"])', "#N/A"],

            'WEEKDAY - Null' => ['@WEEKDAY(null)', "1"],
            'WEEKDAY - Array' => ['@WEEKDAY([])', "1"],
            'WEEKDAY - Empty' => ['@WEEKDAY("")', "#VALUE!"],
            'WEEKDAY - Excel Array' => ['@WEEKDAY({"test", "test2"})', "#VALUE!"],
            'WEEKDAY - Number' => ['@WEEKDAY(100)', "2"],
            'WEEKDAY - Emoji' => ['@WEEKDAY("⭐")', "#VALUE!"],
            'WEEKDAY - Filled array' => ['@WEEKDAY(["test1", "test2", "test3"])', "#N/A"],

            'YEAR' => ['@YEAR("25-Dec-2021")', '2021'],
            'YEAR - Null' => ['@YEAR(null)', "1900"],
            'YEAR - Array' => ['@YEAR([])', "1900"],
            'YEAR - Empty' => ['@YEAR("")', "#VALUE!"],
            'YEAR - Excel Array' => ['@YEAR({"test", "test2"})', "#VALUE!"],
            'YEAR - Number' => ['@YEAR(100)', "1900"],
            'YEAR - Emoji' => ['@YEAR("⭐")', "#VALUE!"],
            'YEAR - Filled array' => ['@YEAR(["test1", "test2", "test3"])', "#N/A"],

            // Formula syntax
            'IF - Nested LEN - true' => ['@IF(@LEN("test") < 5,true,false)', '1'],
            'IF - Nested LEN - false' => ['@IF(@LEN("testtest") < 5,true,false)', ''],
            'IF - Double nested LEN - true' => ['@IF(@LEN(@LEN("test")) < 5,true,false)', '1'],
            'IF - Double nested LEN - false' => ['@IF(@LEN(@LEN("test")) > 5,true,false)', ''],
            'IF - Sum' => ['@IF(10 + 5 = 15, true, false)', '1'],
            'IF - Redirect' => ['@IF(1=1, "https://example.net", "https://example.com")', 'https://example.net'],
            'IF - Without comparision' => ['@IF(@ISBLANK("test"), "YES", "NO")', 'NO'],

            'NOW - Filled array'               => ['@DAY(@NOW(["test1", "test2", "test3"]))', "#N/A"],
            'YMDTODAY - Excel Array'           => ['@YMDTODAY({"test", "test2"})', "1970-01-01+00:00"],
            'YMDTODAY - Number'                => ['@YMDTODAY(123)', "1970-01-01+00:00"],
            'YMDTODAY - Filled array'          => ['@YMDTODAY(["test1", "test2", "test3"])', "#N/A"],
            'YMDNOW - excel array'             => ['@YMDNOW({"test", "test2"})', '1970-01-01T00:00:00+00:00'],
            'YMDNOW - excel array with format' => ['@YMDNOW({"test", "test2"}, "Ymd")', '19700101'],
            'YMDNOW - excel array on 2nd'      => ['@YMDNOW("", {"test", "test2"})', ''],
            'TODAY - Filled array'             => ['@TODAY(["test1", "test2", "test3"], 0, 0)', "#N/A"],
            'LOCALNOW Filled array'            => ['@LOCALNOW(["test1", "test2", "test3"])', "#N/A"],


            ['@CHAR("")', chr(0)],
            ['@CHAR("test")', chr(0)],
            ['@CHAR(54)', '6'],
            ['@FIND("", "test")', '#VALUE!'],
            ['@FIND("", "test", 1)', '#VALUE!'],
            ['@FIND("te", "test")', '1'],
            ['@FIND("te", "TEST")', '#VALUE!'],
            ['@SEARCH("", "test")', '#VALUE!'],
            ['@SEARCH("", "test", 1)', '#VALUE!'],
            ['@SEARCH("te", "test")', '1'],
            ['@SEARCH("te", "TEST")', '1'],
            ['@POWER("", 3)', "0"],
            ['@POWER("test", 3)', "0"],
            ['@POWER("3", 2)', "9"],
            ['@INT("")', "0"],
            ['@INT("test")', "0"],
            ['@INT("3")', "3"],
            ['@INT(3)', "3"],
            ['@ROUND("")', "0"],
            ['@ROUND("test")', "0"],
            ['@ROUND("3")', "3"],
            ['@ROUND(3.5)', "4"],
            ['@ROUND("3.5")', "4"],
            ['@ROUND("3.2")', "3"],
            ['@ROUND("3.1")', "3"],
            ['@ROUND("3.3558")', "3"],
            ['@ABS("")', "0"],
            ['@ABS("test")', "0"],
            ['@ABS("3")', "3"],
            ['@ABS(3)', "3"],
            ['@ABS(-3.5)', "3.5"],
            ['@ABS("-3.5")', "3.5"],
            ['@POWER(234, 3)', "12812904"],
            ['@POWER(3, 3)', "27"],
            ['@POWER(2)', "4"],
        ];
    }

    // Check back-reference escaping (see: http://stackoverflow.com/questions/336416/pregreplace-is-replacing-signs)
    public function testBackReference(): void
    {
        $Formula = new PhpSpreadsheetProcessor();
        $r = $Formula->evaluate("%%VAR%%", ["VAR" => '$10,000.00']);
        self::assertEquals('$10,000.00', $r);

        $r = $Formula->evaluate("%%VAR%%", ["VAR" => '$AA']);
        self::assertEquals('$AA', $r);
    }

    public function testRecursiveCompute(): void
    {
        $Formula = new PhpSpreadsheetProcessor();

        $f = "@IF(%%VAR%%>5,1,2)";

        $r = $Formula->evaluate($f, ["VAR" => 6]);
        self::assertFalse(is_array($r));
        self::assertEquals(1, $r);

        $f = ["@IF(%%VAR%%>5,1,2)", "@IF(%%VAR%%>5,5,10)", "@IF(%%VAR%%>5,50,100)"];

        $r = $Formula->evaluate($f, ["VAR" => 6]);
        self::assertTrue(is_array($r));
        self::assertEquals(1, $r[0]);
        self::assertEquals(5, $r[1]);
        self::assertEquals(50, $r[2]);

        $r = $Formula->evaluate($f, ["VAR" => 0]);
        self::assertTrue(is_array($r));
        self::assertEquals(2, $r[0]);
        self::assertEquals(10, $r[1]);
        self::assertEquals(100, $r[2]);

        $f = ["@IF(%%VAR%%>5,1,2)", ["@IF(%%VAR%%>5,5,10)", "@IF(%%VAR%%>5,50,100)"]];
        $r = $Formula->evaluate($f, ["VAR" => 6]);
        self::assertTrue(is_array($r));
        self::assertEquals(1, $r[0]);
        self::assertTrue(is_array($r[1]));
        self::assertEquals(5, $r[1][0]);
        self::assertEquals(50, $r[1][1]);
    }

    public function testYMDDates(): void
    {
        $Formula = new PhpSpreadsheetProcessor();

        $f = "@YMDNOW()";
        $r = $Formula->evaluate($f);
        self::assertEquals($r, date('Y-m-d\TH:i:sP'), $f . ' failed to match date(Y-m-d\TH:i:sP)');

        $f = "@YMDNOW(\"+1 hours\")";
        $r = $Formula->evaluate($f);
        self::assertEquals(
            $r,
            date('Y-m-d\TH:i:sP', strtotime('+1 hours')),
            $f . ' failed to match date(Y-m-d\TH:i:sP, strtotime(+1 hours))'
        );

        $f = "@YMDTODAY()";
        $r = $Formula->evaluate($f);
        self::assertEquals($r, date('Y-m-dP'), $f . ' failed to match date(Y-m-dP)');

        $f = "@YMDTODAY(\"+1 days\")";
        $r = $Formula->evaluate($f);
        self::assertEquals(
            $r,
            date('Y-m-dP', strtotime('+1 days')),
            $f . ' failed to match date(Y-m-dP, strtotime(+1 days))'
        );
    }

    public function testHTMLEntitiesEvaluation(): void
    {
        $Formula = new PhpSpreadsheetProcessor();
        $r = $Formula->evaluate("@IF(\"Bande dessinée\"=\"Bande dessinée\",\"TRUE\",\"FALSE\")");
        self::assertEquals('TRUE', $r);

        //Test HTML entity evaluation
        $r = $Formula->evaluate("@IF(\"Bande dessinée\"=\"Bande dessin&eacute;e\",\"TRUE\",\"FALSE\")");
        self::assertEquals('TRUE', $r);

        $f = "@IF(&quot;yes&quot;=&quot;yes&quot;,&quot;Bob&quot;,&quot;Smith&quot;)";
        $r = $Formula->evaluate($f);
        self::assertEquals('Bob', $r);
    }

    public function testEmptyValueEvaluation(): void
    {
        $Formula = new PhpSpreadsheetProcessor();
        $r = $Formula->evaluate("");
        self::assertEquals("", $r);
    }


    public function testCompute(): void
    {
        $Formula = new PhpSpreadsheetProcessor();

        $formula
            = '@IF(@LEFT(%%tfa_Postcode%%,1)="3","VIC New Member List", @IF(@OR(@LEFT(%%tfa_Postcode%%, 2)= "26",@LEFT(%%tfa_Postcode%% , 2)= "29"), "ACT New Member List", @IF(@AND(@LEFT(%%tfa_Postcode%%, 1)= "2",@OR(@LEFT(%%tfa_Postcode%% , 2) <> "26",@LEFT(%%tfa_Postcode%% , 2)<> "29")), "NSW New Member List", @IF(@LEFT(%%tfa_Postcode%%, 2)= "42","GC New Member List", @IF(@LEFT(%%tfa_Postcode%%, 1)= "5", "SA New Member List", @IF(@LEFT(%%tfa_Postcode%%, 1)= "6", "WA New Member List", @IF(@LEFT(%%tfa_Postcode%%, 1)= "7", "TAS New Member List", @IF(@AND(@LEFT(%%tfa_Postcode%%, 1)= "4", @LEFT(%%tfa_Postcode%%, 2)<> "42"), "QLD New Member List", @IF(%%tfa_calculatedpostco%% < 2,"NT New Member List","New Member Calling Campaigns 2010")))))))))';
        $r = $Formula->evaluate($formula, ['tfa_Postcode' => 47404, 'tfa_calculatedpostco' => 47.404]);
        self::assertEquals("QLD New Member List", $r);

        $r = $Formula->evaluate($formula, ['tfa_Postcode' => 30303, 'tfa_calculatedpostco' => 30.303]);
        self::assertEquals("VIC New Member List", $r);

        $r = $Formula->evaluate($formula, ['tfa_Postcode' => 29030, 'tfa_calculatedpostco' => 29.030]);
        self::assertEquals("ACT New Member List", $r);

        $r = $Formula->evaluate($formula, ['tfa_Postcode' => 22030, 'tfa_calculatedpostco' => 22.030]);
        self::assertEquals("NSW New Member List", $r);

        $formula = '@IF(%%abc%% = "700","None", "z") @IF(%%abc%% = "625",%%abc%% , "z")';
        $r = $Formula->evaluate($formula, ['abc' => 100]);
        self::assertEquals('z z', $r, $formula);

        $formula = '@IF("abc" = "700","None", @IF(%%abc%% = "625",%%abc%% , "z"))';
        $r = $Formula->evaluate($formula, ['abc' => 100]);
        self::assertEquals('z', $r, $formula);

        $formula = '@IF(%%abc%% = "700","None", @IF(%%abc%% = "625",%%abc%% , "z"))';
        $r = $Formula->evaluate($formula, ['abc' => 100]);
        self::assertEquals('z', $r, $formula);

        $formula = '@IF(%%tfa_CreditScore%%>=700,"None",@IF(%%tfa_CreditScore%%>=625,"10 Percent of Lease Amount", "z"))';
        $r = $Formula->evaluate($formula, ['tfa_CreditScore' => 100]);
        self::assertEquals('z', $r, $formula);

        $r = $Formula->evaluate($formula, ['tfa_CreditScore' => 100]);
        self::assertEquals('z', $r, $formula);
    }

    public function testGreedyMatch(): void
    {
        $Formula = new PhpSpreadsheetProcessor();

        $formula = '%%tfa_A%% %%tfa_AA%% %%tfa_AAA%%';

        $r = $Formula->evaluate($formula, ['tfa_A' => '1', 'tfa_AA' => '2', 'tfa_AAA' => '3']);
        self::assertEquals("1 2 3", $r);

        $r = $Formula->evaluate($formula, ['tfa_AA' => '2', 'tfa_AAA' => '3']);
        self::assertEquals(" 2 3", $r);

        $r = $Formula->evaluate($formula, ['tfa_A' => '1', 'tfa_AAA' => '3']);
        self::assertEquals("1  3", $r);

        $r = $Formula->evaluate($formula, ['tfa_A' => '1', 'tfa_AA' => '2']);
        self::assertEquals("1 2 ", $r);

        $r = $Formula->evaluate($formula, ['tfa_A' => '1']);
        self::assertEquals("1  ", $r);

        $r = $Formula->evaluate($formula, ['tfa_AA' => '2']);
        self::assertEquals(" 2 ", $r);

        $r = $Formula->evaluate($formula, ['tfa_AAA' => '3']);
        self::assertEquals("  3", $r);
    }

    static public function formulasWithHTMLCodeProvider(): array
    {
        return [
            "Invalid placement of HTML #1" => ['<div>@IF(%%A%%>700,B,</div><div>"<br/>C")</div>',"<div>#N/A</div>"],
            "Invalid placement of HTML #2" => ['<p>@IF(%%A%%>700,B,<br/>&nbsp;&nbsp;</p>"<br/>C")',"<p>#N/A" ],
            "Invalid placement of HTML #3" => ['<p>@IF(%%A%%>700, "B", </p><p>"<pre><p>C</p></pre></p>")', "<p>#N/A"]
        ];
    }

    /**
     * Note: This is a change in behavior from prior formula implementation (*).
     * Markup is no longer stripped before evaluation.
     *
     * (*) https://git.formassembly.com/Formassembly/formassembly/blob/72f4548d1b73a79df1583391c937bdb35405859e/api_v2/app/Formula/Traits/FormulaProcessorTrait.php#L168
     * @dataProvider formulasWithHTMLCodeProvider
     */
    public function testFormulasWithHTMLCode($formula, $expected): void
    {
        $Formula = new PhpSpreadsheetProcessor();

        $r = $Formula->evaluate($formula, ['A' => '670']);
        self::assertEquals($expected, $r);
    }

    public function testFormulasWithLineBreak(): void
    {
        $formula = <<<EOD
@IF(670>=700,"None",
@IF(670>=625,"10 Percent of Lease Amount", "z"))
EOD;
        $Formula = new PhpSpreadsheetProcessor();
        $r = $Formula->evaluate($formula, ['tfa_CreditScore' => '670']);
        self::assertEquals("10 Percent of Lease Amount", $r);

        $formula = <<<EOD
@IF(670>=700,"None", @IF(670>=625,"10 Percent of
Lease Amount", "z"))
EOD;
        $Formula = new PhpSpreadsheetProcessor();
        $r = $Formula->evaluate($formula);
        self::assertEquals("10 Percent of\nLease Amount", $r);
    }

    /**
     * Tests that big numbers are formatted as plain integer numbers by excel functions,
     * and that no scientific notation is used.
     *
     * @group FA-4082
     */
    public function testScientificNotationPreventedInExcelFormattingOfBigIntegerNumbers(): void
    {
        $formula = new PhpSpreadsheetProcessor();

        $input = '@CONCATENATE("",9123456789012345,-9123456789012345,51)';

        $computed = $formula->evaluate($input);
        self::assertEquals("9123456789012345-912345678901234551",
            $computed,
            "Using a big integer number in an Excel string formula should not use scientific notation, but it did."
        );
    }

    /**
     * Tests the concatenation of multiple numeric strings (preserving leading
     * and trailing zeros).
     */
    public function testNumericStringConcatenation(): void
    {
        $formula = new PhpSpreadsheetProcessor();

        $input = '@CONCATENATE(%%tfa_1%%, %%tfa_2%%, %%tfa_3%%)';
        $variables = [
            'tfa_1' => '00001234',
            'tfa_2' => '1234567890123456',
            'tfa_3' => '12345.67890',
        ];

        self::assertEquals(
            $variables['tfa_1'] . $variables['tfa_2'] . $variables['tfa_3'],
            $formula->evaluate($input, $variables),
            "Concatenating numeric strings returned an unexpected result."
        );
    }

    /**
     * Tests the summation of both numeric and non-numeric strings (expecting
     * only the sum of numeric values).
     */
    public function testStringSummation(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input   = '@SUM(00001234, "ABCDEFG", 12345.67890)';

        self::assertEquals(13579.6789, $formula->evaluate($input),
            "Summation of strings returned an unexpected result."
        );
    }

    /**
     * Tests the preservation of leading/trailing zeros on numeric strings not
     * passed through formulas.
     */
    public function testNumericStringLeadingAndTrailingZeroPreservation(): void
    {
        $formula = new PhpSpreadsheetProcessor();

        $input = '00001234.0000';

        self::assertSame('00001234.0000', $formula->evaluate($input),
            "Numeric string's leading and trailing zeros were not preserved."
        );
    }

    /**
     * Tests equality of numeric strings with different leading/trailing zeros.
     *
     * PhpSpreadsheet executes generated code using '==', so this *should* never fail.
     */
    public function testLexicographicallyDifferentNumericStringEquality(): void
    {
        $formula = new PhpSpreadsheetProcessor();

        $input = '@IF(00001234=1234.0000, TRUE, FALSE)';

        self::assertTrue((bool) $formula->evaluate($input),
            "Lexicographically different numeric strings were not determined as equal."
        );
    }

    /**
     * Checks that left/right double quotation marks are handled as regular double-quotes
     * "\u{201C}" // Left Double Quotation Mark
     * "\u{201D}" // Right Double Quotation Mark
     */
    public function testLeftRightDoubleQuotesReplacement(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input   = "@IF(@TRUE(), \u{201C}text in curly quotes\u{201D},\"other\")";
        self::assertEquals("text in curly quotes", $formula->evaluate($input));
    }

    public function testIgnoreNbsp(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input   = "@IF(@TRUE(), \"fine\", \"not fine\" &nbsp;)";
        self::assertEquals("fine", $formula->evaluate($input));
    }

    /**
     * Tests invalid formula strings.
     *
     * PhpSpreadsheet executes generated code using '==', so this *should* never fail.
     */
    public function testInvalidFormulaStrings(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input = '@CONCATENATE("Mike", "John)';
        self::assertEquals("#N/A", $formula->evaluate($input));
    }

    public function testInvalidFormulaStrings3(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input = '@IF(%%tfa_1%%\'%%tfa_2%%, TRUE, FALSE)';
        $variables = [
            'tfa_1' => '00001234',
            'tfa_2' => '1234.0000',
        ];
        self::assertEquals("#N/A", $formula->evaluate($input, $variables));
    }

    public function testInvalidFormulaStrings4(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input = '@SUM({%%tfa_1%%, %%tfa_2%%, %%tfa_3%%)';
        $variables = [
            'tfa_1' => '00001234',
            'tfa_2' => '1234.0000',
        ];
        self::assertEquals("#N/A", $formula->evaluate($input, $variables));
    }

    public function testInvalidFormulaStrings5(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input = '@SUM("Mike" "John")';
        self::assertEquals("#N/A", $formula->evaluate($input));
    }

    public function testInvalidFormulaStrings6(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $input = '@CONCATENATE("Mike", "John)%%';
        self::assertEquals("#N/A", $formula->evaluate($input));
    }

    public function testValidFormulaStrings8(): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $variables = [
            'tfa_1' => '00001234',
            'tfa_2' => '1234.0000',
        ];

        $input = '@IF(@LEN(%%tfa_1%%) > 5,true,false)';
        self::assertEquals(
            "1",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF(@LEN(%%tfa_1%%) > 9,true,false)';
        self::assertEquals(
            "",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF(@LEN(%%tfa_1%%) < 9,true,false)';
        self::assertEquals(
            "1",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF(@LEN(%%tfa_1%%) < 5,true,false)';
        self::assertEquals(
            "",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF(@LEN(@LEN(%%tfa_1%%))=1,true,false)';
        self::assertEquals(
            "1",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF( @LEN( @LEN(%%tfa_1%%) ) = 1, true, false)';
        self::assertEquals(
            "1",
            $formula->evaluate($input, $variables)
        );

        $input = '@IF("1" = @LEN( @LEN( %%tfa_1%% ) ), true, false)';
        self::assertEquals(
            "1",
            $formula->evaluate($input, $variables)
        );

        $input = '   @IF("1" = @LEN(@LEN( %%tfa_1%%) ), true, false) - test';
        self::assertEquals(
            "   1 - test",
            $formula->evaluate($input, $variables)
        );
    }



    static public function dateTimeFunctionsProvider(): array
    {
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('UTC'));

        $serialTime    = (string) floor(25569 + (time() / 86400)); // Converts UNIX timestamp to SERIAL (used by excel)
        $weekDay       = (string) ((int) date('w') + 1);
        $dayOfTheMonth = $dateTime->format("j");
        $ymdToday      = $dateTime->format('Y-m-dP');
        $ymdTomorrow   = date('Ymd', strtotime('+1 days'));
        $localToday    = strftime('%x');

        return [
            'WEEKDAY'                           => ['@WEEKDAY(@TODAY())', $weekDay],
            'DAY'                               => ['@DAY(@NOW())', $dayOfTheMonth],
            'DAY - Null'                        => ['@DAY(@NOW(null))', $dayOfTheMonth],
            'DAY - Array'                       => ['@DAY(@NOW([]))', $dayOfTheMonth],
            'DAY - Empty'                       => ['@DAY(@NOW(""))', $dayOfTheMonth],
            'DAY - Excel Array'                 => ['@DAY(@NOW({"test", "test2"}))', $dayOfTheMonth],
            'DAY - Number'                      => ['@DAY(@NOW(100))', $dayOfTheMonth],
            'DAY - Emoji'                       => ['@DAY(@NOW("⭐"))', $dayOfTheMonth],
            'YMDTODAY - without params'         => ['@YMDTODAY()', $ymdToday],
            'YMDTODAY - with offset and format' => ['@YMDTODAY("+1 days", "Ymd")', $ymdTomorrow],
            'YMDTODAY - Null'                   => ['@YMDTODAY(null)', $ymdToday],
            'YMDTODAY - Array'                  => ['@YMDTODAY([])', $ymdToday],
            'YMDTODAY - Empty'                  => ['@YMDTODAY("")', $ymdToday],
            'LOCALTODAY'                        => ['@LOCALTODAY()', $localToday],
            'LOCALTODAY - With param'           => ['@LOCALTODAY(123)', $localToday],
            'TODAY'                             => ['@TODAY()', $serialTime],
            'TODAY - Null'                      => ['@TODAY(null, 0, 0)', $serialTime],
            'TODAY - Array'                     => ['@TODAY([], 0, 0)', $serialTime],
            'TODAY - Empty'                     => ['@TODAY("", 0, 0)', $serialTime],
            'TODAY - Excel Array'               => ['@TODAY({"test", "test2"}, 0, 0)', $serialTime],
            'TODAY - Number'                    => ['@TODAY(100, 0, 0)', $serialTime],
            'TODAY - Emoji'                     => ['@TODAY("⭐", 0, 0)', $serialTime],
        ];
    }

    /**
     * @param mixed $expression
     * @param       $expected
     *
     * @dataProvider dateTimeFunctionsProvider
     */
    public function testDateTimeFunctions(mixed $expression, $expected): void
    {
        $result = (new PhpSpreadsheetProcessor())->evaluate($expression);
        self::assertSame($expected, $result);
    }

    static public function timeSensitiveFunctionsProvider(): array
    {
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('UTC'));

        $ymdShortToday = fn() => $dateTime->format('Ymd');
        $ymdTomorrow   = fn() => date('Ymd', strtotime('+1 days'));
        $atomToday     = fn() => $dateTime->format(DATE_ATOM);
        $localNow      = fn() => strftime('%x %H:%M');

        return [
            'YMDNOW'                            => ['@YMDNOW("+1 days", "Ymd")', $ymdTomorrow],
            'YMDNOW - null'                     => ['@YMDNOW(null)', $atomToday],
            'YMDNOW - array'                    => ['@YMDNOW([])', $atomToday],
            'YMDNOW - empty string'             => ['@YMDNOW("")', $atomToday],
            'YMDNOW - null with format'         => ['@YMDNOW(null, "Ymd")', $ymdShortToday],
            'YMDNOW - array with format'        => ['@YMDNOW([], "Ymd")', $ymdShortToday],
            'YMDNOW - empty string with format' => ['@YMDNOW("", "Ymd")', $ymdShortToday],
            'YMDNOW - null on 2nd'              => ['@YMDNOW("", null)', $atomToday],
            'YMDNOW - array on 2nd'             => ['@YMDNOW("", [])', $atomToday],
            'YMDNOW - empty string on 2nd'      => ['@YMDNOW("", "")', $atomToday],
            'LOCALNOW'                          => ['@LOCALNOW()', $localNow],
            'LOCALNOW - Null'                   => ['@LOCALNOW(null)', $localNow],
            'LOCALNOW - Array'                  => ['@LOCALNOW([])', $localNow],
            'LOCALNOW - Empty'                  => ['@LOCALNOW("")', $localNow],
            'LOCALNOW - Excel Array'            => ['@LOCALNOW({"test", "test2"})', $localNow],
            'LOCALNOW - Emoji'                  => ['@LOCALNOW("⭐")', $localNow],
        ];
    }
    /**
     * @param mixed $expression
     * @param       $expected
     *
     * @dataProvider timeSensitiveFunctionsProvider
     */
    public function testTimeSensitiveDateTimeFunctions(mixed $expression, $expected): void
    {
        $result = (new PhpSpreadsheetProcessor())->evaluate($expression);
        $this->assertTimeEquals($result, $expected());
    }

    private function assertTimeEquals(string $testedTime, string $shouldBeTime, int $timeTolerance = 30): void
    {
        $testedTime     = strtotime($testedTime);
        $shouldBeTime   = strtotime($shouldBeTime);
        $toleranceRange = range($shouldBeTime, $shouldBeTime + $timeTolerance);
        self::assertContains($testedTime, $toleranceRange, print_r($toleranceRange,true));
    }

    static public function dayProvider(): array
    {
        return  [
            ['2021-12-11', '11'],        // December 11th, 2021
            ['21-12-11', '11'],          // December 11th, 2021
            ['2021-Dec-11', '11'],       // December 11th, 2021
            ['Dec 15 2022', '15'],       // December 15th, 2022
            ['Dec 15th, 2022', '15'],    // December 15th, 2022
            ['December 1st, 2022', '1'], // December 1st, 2022
            ['2022-12-10', '10'],        // December 10th, 2022
            ['10 December 22', '10'],    // December 10th, 2022
            ['10-Dec-22', '10'],         // December 10th, 2022
            ['15/11/2022', '15'],        // November 15th, 2022 - DD/MM
        ];
    }

    /**
     * Tests spreadsheet's date detection
     * @dataProvider dayProvider
     */
    public function testCorrectDateFormatUsed($input, $expected): void
    {
        $formula = new PhpSpreadsheetProcessor();
        $res = $formula->evaluate('@DAY(%%var%%)', ['var' => $input]);
        self::assertSame($expected, $res);
    }

    static public function ambiguousDayProvider(): array
    {
        return [
            ['11/10/2022', '10'],       // November 10th, 2022 - MM/DD
            ['09/10/2022', '10'],       // September 10th, 2022 - MM/DD
        ];
    }

    /**
     * Tests spreadsheet's date detection
     * @dataProvider ambiguousDayProvider
     */
    public function testAmbiguousDateFormatUsed($input, $expected): void
    {
        self::markTestSkipped('Date detection in variable wrongly assumes DD/MM/YYYY. Skipped for now.');

        $formula = new PhpSpreadsheetProcessor();
        $res = $formula->evaluate('@DAY(%%var%%)', ['var' => $input]);
        self::assertSame($expected, $res);
    }

    static public function formulasWithInvalidSyntax(): array
    {
        return [
            "Invalid function. Parser skips."                                               => ['@BAD()', '@BAD()'],
            "Invalid function call. Parser skips."                                          => ['@TRUE ()', '@TRUE ()'],
            "Invalid function call, with parameters. Parser skips."                         => ['@IF (1=1, 1, 2)', '@IF (1=1, 1, 2)'],
            "Invalid nested function call. Parser stops at first closing parenthesis."      => ['@IF( @AND (1, 1), 1, 2)', '#N/A, 1, 2)'],
            "Invalid nested function. Parser stops at the first closing parenthesis."       => ['@IF(@BAD(%%tfa_1%%) > 5,true,false)', '#N/A > 5,true,false)'],
            "Invalid deep nested function. Parser stops at the second closing parenthesis." => ['@IF(@LEN(@BAD(%%tfa_1%%)) > 5,true,false)', '#N/A > 5,true,false)'],
            "Invalid function with alias resolution. Parser resolve only alias."            => ['@BAD(%%tfa_1%%)', '@BAD(123)'],
            "Invalid function call in mixed content. Parser skips"                          => ['Something: @IF (1=1, 1, 2)', 'Something: @IF (1=1, 1, 2)'],
        ];
    }

    static public function formulasWithValidSyntax(): array
    {
        return [
            ['@IF( @LEN(%%tfa_1%%) > 2,true,false)', '1'],
            ['@IF( @LEN(  %%tfa_1%%  ) > 2,true,false)', '1'],
            ['@IF(@LEN(  %%tfa_1%%  ) > 2,true,false)', '1'],
            ['@IF(1=1, 1, 2)', '1'],
            ['@IF( 1 = 1 , 1 , 2 )', '1'],
            ['@IF( 1  =  1 , "a" , "b" )', 'a'],
            ['Something: @IF( 1=1 , 1, 2)', 'Something: 1'],
            ['@AND(1, 2)', '1'],
            ['@AND( 1 , 2   )', '1'],
            ['@IF( @AND(1, 1), 1 , 2 )', '1'],
            ['@UPPER( "test@mail.com (test)" )', 'TEST@MAIL.COM (TEST)'],
            ['Email: test@email.com ( @IF( 1 = " (Test)", "John", "Doe") )', 'Email: test@email.com ( Doe )'],
            ['Twitter: @myuser @IF( 1 = " (Test)", "John", "Doe")', 'Twitter: @myuser Doe'],
            ['Twitter: @myuser @IF( "" = " (Test)", "John", "Doe")', 'Twitter: @myuser Doe'],
            ['Twitter: @myuser', 'Twitter: @myuser'],
            ['abc @1.23()', 'abc @1.23()'],
            ['abc @ TEST()', 'abc @ TEST()'],
            ['join us @ 12(PM)', 'join us @ 12(PM)'],
            ['join us @ 12(PM)', 'join us @ 12(PM)'],
            ['Twitter: @IF(%%tfa_1%% = 123, "@myuser (Name)", "@otheruser (test)")', 'Twitter: @myuser (Name)'],
            ['Twitter: @myuser (Name)', 'Twitter: @myuser (Name)'],
            ['Twitter: @myuser(Name)', 'Twitter: @myuser(Name)'],
        ];
    }

    /**
     * @dataProvider formulasWithInvalidSyntax
     * @dataProvider formulasWithValidSyntax
     */
    public function testSyntaxCheck(string $formula, string $expected): void
    {
        $result = (new PhpSpreadsheetProcessor())->evaluate($formula, ['tfa_1' => 123]);
        self::assertEquals($expected, $result, $formula);
    }

}
