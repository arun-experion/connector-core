<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum as PhpSpreadsheetSum;

final class Sum implements CustomFunction
{
    public function getName(): string
    {
        return 'SUM';
    }

    public function getArgumentCount(): string
    {
        return '1+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return float|string
     */
    public static function compute()
    {
        return PhpSpreadsheetSum::sumIgnoringStrings(...func_get_args());
    }
}
