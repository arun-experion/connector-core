<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

final class Power implements CustomFunction
{
    public function getName(): string
    {
        return 'POWER';
    }

    public function getArgumentCount(): string
    {
        return '1,2';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return object|int|float|string
     */
    public static function compute()
    {

        $x = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0)) ?: 0;
        $y = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1, 2));

        // Compatibility fix - PHP Excel returns 0 for empty inputs
        if (!is_numeric($x)) {
            return 0;
        }

        $x = (int) $x;
        $y = (int) $y;

        // Validate parameters
        if ($x < 0) {
            return Functions::NAN();
        }
        if ($x == 0 && $y <= 0) {
            return Functions::DIV0();
        }

        return pow($x, $y);
    }
}
