<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class RandBetween implements CustomFunction
{
    public function getName(): string
    {
        return 'RANDBETWEEN';
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
        $min = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0));
        $max = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1));

        if (!is_numeric($min) || !is_numeric($max)) {
            $min = !is_numeric($min) ? 0 : $min;
            $max = !is_numeric($max) ? 0 : $max;
        }

        if ($min == 0 && $max == 0) {
            return (rand(0, 10000000)) / 10000000;
        } else {
            return rand($min, $max);
        }
    }
}
