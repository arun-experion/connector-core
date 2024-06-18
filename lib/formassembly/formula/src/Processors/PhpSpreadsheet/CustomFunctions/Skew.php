<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Deviations;

final class Skew implements CustomFunction
{
    public function getName(): string
    {
        return 'SKEW';
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
     * @return float|int|string
     */
    public static function compute()
    {
        return Deviations::skew(ReduceToNumerics::givenValues(Flatten::anArrayIndexed(func_get_args())));
    }
}
