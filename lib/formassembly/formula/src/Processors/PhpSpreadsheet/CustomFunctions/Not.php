<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

final class Not implements CustomFunction
{
    public function getName(): string
    {
        return 'NOT';
    }

    public function getArgumentCount(): string
    {
        return '1';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): bool
    {
        $logical = Flatten::singleValue(func_get_arg(0) ?: "");

        if (is_string($logical)) {
            $logical = mb_strtoupper($logical, 'UTF-8');
            if (($logical == 'TRUE') || ($logical == Calculation::getTRUE())) {
                return false;
            } elseif (($logical == 'FALSE') || ($logical == Calculation::getFALSE())) {
                return true;
            }
        }

        return !$logical;
    }
}
