<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\SeriesSum as SpreadsheetSeriesSum;

final class SeriesSum implements CustomFunction
{
    public function getName(): string
    {
        return 'SERIESSUM';
    }

    public function getArgumentCount(): string
    {
        return '4+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return array|float|string
     */
    public static function compute()
    {
        $args = func_get_args();

        $value = array_shift($args);
        $power = array_shift($args);
        $step = array_shift($args);

        return SpreadsheetSeriesSum::evaluate(
            $value,
            $power,
            $step,
            ReduceToNumerics::givenValues(Flatten::anArray($args))
        );
    }
}
