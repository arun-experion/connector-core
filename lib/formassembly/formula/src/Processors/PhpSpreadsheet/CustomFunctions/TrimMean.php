<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages\Mean;

final class TrimMean implements CustomFunction
{
    public function getName(): string
    {
        return 'TRIMMEAN';
    }

    public function getArgumentCount(): string
    {
        return '2+';
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
        return Mean::trim(ReduceToNumerics::givenValues(func_get_args()));
    }
}
