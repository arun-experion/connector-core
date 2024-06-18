<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Round as PhpSpreadsheetRound;

final class Round implements CustomFunction
{
    public function getName(): string
    {
        return 'ROUND';
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
     * @return mixed
     */
    public static function compute()
    {
        $value = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0)) ?: 0;
        $round = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1, 0)) ?: 0;

        // Compatibility fix - PHP Excel returns 0 for non numeric inputs
        if (!is_numeric($value)) {
            return 0;
        }

        return PhpSpreadsheetRound::round($value, $round);
    }
}
