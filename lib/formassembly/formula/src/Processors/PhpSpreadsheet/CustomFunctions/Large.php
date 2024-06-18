<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Size;

final class Large implements CustomFunction
{
    public function getName(): string
    {
        return 'LARGE';
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
        $position = array_pop($args);

        return Size::large(ReduceToNumerics::givenValues(Flatten::anArray($args)), $position);
    }
}
