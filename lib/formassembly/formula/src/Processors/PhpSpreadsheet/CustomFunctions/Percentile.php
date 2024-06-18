<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Percentiles;

final class Percentile implements CustomFunction
{
    public function getName(): string
    {
        return 'PERCENTILE';
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
        $args = func_get_args();
        $percentile = array_pop($args);

        return Percentiles::PERCENTILE(ReduceToNumerics::givenValues(Flatten::anArray($args)), $percentile);
    }
}
