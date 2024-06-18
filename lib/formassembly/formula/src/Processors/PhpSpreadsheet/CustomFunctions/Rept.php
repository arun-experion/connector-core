<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class Rept implements CustomFunction
{
    public function getName(): string
    {
        return 'REPT';
    }

    public function getArgumentCount(): string
    {
        return '1+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): string
    {
        $str = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0));
        $rpt = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1, 0));

        if (!is_numeric($rpt)) {
            $rpt = 0;
        }

        return str_repeat((string)$str, (int)$rpt);
    }
}
