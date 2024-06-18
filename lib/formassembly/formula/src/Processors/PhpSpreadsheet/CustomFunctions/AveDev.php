<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;

final class AveDev implements CustomFunction
{
    public function getName(): string
    {
        return 'AVEDEV';
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
        return Averages::averageDeviations(ReduceToNumerics::givenValues(Flatten::anArrayIndexed(func_get_args())));
    }
}
