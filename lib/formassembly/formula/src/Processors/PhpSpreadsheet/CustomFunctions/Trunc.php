<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class Trunc implements CustomFunction
{
    public function getName(): string
    {
        return 'TRUNC';
    }

    public function getArgumentCount(): string
    {
        return '0+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return float|int
     */
    public static function compute()
    {
        $value = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0)) ?: 0;
        $digits = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1)) ?: 0;

        if (!is_numeric($value)) {
            $value = 0;
        }

        if (!is_numeric($digits)) {
            $digits = 0;
        }

        $value = $value * pow(10, $digits);
        $value = intval($value);
        $value = $value / pow(10, $digits);

        return $value;
    }
}
