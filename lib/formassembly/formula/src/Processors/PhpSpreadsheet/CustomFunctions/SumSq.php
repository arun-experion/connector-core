<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\SumSquares;

final class SumSq implements CustomFunction
{
    public function getName(): string
    {
        return 'SUMSQ';
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
        return SumSquares::sumSquare(ReduceToNumerics::givenValues(Flatten::anArray(func_get_args())));
    }
}
