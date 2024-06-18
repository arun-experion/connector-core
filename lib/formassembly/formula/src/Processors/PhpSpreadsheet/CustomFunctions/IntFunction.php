<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\IntClass;

final class IntFunction implements CustomFunction
{
    public function getName(): string
    {
        return 'INT';
    }

    public function getArgumentCount(): string
    {
        return '1';
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
        $value = Flatten::singleValue(func_get_arg(0) ?? null);

        // Compatibility fix - PHP Excel returns 0 for non numeric inputs
        if (!is_numeric($value)) {
            return 0;
        }

        return IntClass::evaluate($value);
    }
}
