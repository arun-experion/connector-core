<?php

namespace FormAssembly\Formula\Processors\PhpSpreadsheet;

use FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions as Custom;
use FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions\CustomFunction;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use ReflectionClass;

class Spreadsheet extends PhpSpreadsheet
{
    /** @var CustomFunction[] */
    private $customFunctions = [];

    public function __construct()
    {
        parent::__construct();

        $this->customFunctions = [
            new Custom\Abs(),
            new Custom\AddSlashes(),
            new Custom\AveDev(),
            new Custom\Average(),
            new Custom\Compute(),
            new Custom\Contains(),
            new Custom\Char(),
            new Custom\DevSq(),
            new Custom\Find(),
            new Custom\HarMean(),
            new Custom\IntFunction(),
            new Custom\IsBlank(),
            new Custom\IsNumber(),
            new Custom\IfError(),
            new Custom\Kurtosis(),
            new Custom\Large(),
            new Custom\Length(),
            new Custom\Left(),
            new Custom\LocalNow(),
            new Custom\LocalToday(),
            new Custom\LogicalAnd(),
            new Custom\LogicalOr(),
            new Custom\Lower(),
            new Custom\Max(),
            new Custom\Median(),
            new Custom\Mid(),
            new Custom\Min(),
            new Custom\Mode(),
            new Custom\Not(),
            new Custom\Percentile(),
            new Custom\Power(),
            new Custom\Product(),
            new Custom\Proper(),
            new Custom\Quartile(),
            new Custom\Quotient(),
            new Custom\RandBetween(),
            new Custom\Rept(),
            new Custom\Right(),
            new Custom\Round(),
            new Custom\Search(),
            new Custom\SeriesSum(),
            new Custom\Skew(),
            new Custom\Small(),
            new Custom\StDev(),
            new Custom\StDevP(),
            new Custom\Subtotal(),
            new Custom\Substitute(),
            new Custom\Sum(),
            new Custom\SumSq(),
            new Custom\Text(),
            new Custom\Trim(),
            new Custom\TrimMean(),
            new Custom\Trunc(),
            new Custom\Upper(),
            new Custom\UrlDecode(),
            new Custom\UrlEncode(),
            new Custom\Variance(),
            new Custom\VarianceP(),
            new Custom\YmdNow(),
            new Custom\YmdToday(),
        ];

        $this->calculationEngine = $this->customizedCalculationEngine();
    }

    /**
     * Creates a custom instance of the calculation engine with our customized functions
     *
     * @return Calculation
     */
    private function customizedCalculationEngine()
    {
        $calculation = new Calculation($this);
        $reflection = new ReflectionClass($calculation);

        $spreadsheetFunctionsProperty = $reflection->getProperty('phpSpreadsheetFunctions');
        $spreadsheetFunctionsProperty->setAccessible(true);

        $enhancedSpreadsheetFunctions = array_reduce(
            $this->customFunctions,
            static function (array $spreadsheetFunctions, CustomFunction $function): array {
                $spreadsheetFunctions[$function->getName()] = [
                    'category' => 'custom',
                    'functionCall' => [get_class($function), 'compute'],
                    'argumentCount' => $function->getArgumentCount(),
                ];

                if ($function->shouldPassCellReference()) {
                    $spreadsheetFunctions[$function->getName()]['passCellReference'] = true;
                }

                return $spreadsheetFunctions;
            },
            $spreadsheetFunctionsProperty->getValue()
        );

        array_walk($enhancedSpreadsheetFunctions, function (&$function) {
            $argsCount = $function['argumentCount'];
            $function['argumentCount'] = $argsCount == "0" ? "0+" : $argsCount; // PHP Spreadsheet throws an error if we pass more args than expected
        });

        $spreadsheetFunctionsProperty->setValue($enhancedSpreadsheetFunctions);
        $spreadsheetFunctionsProperty->setAccessible(false);

        return $calculation;
    }
}
