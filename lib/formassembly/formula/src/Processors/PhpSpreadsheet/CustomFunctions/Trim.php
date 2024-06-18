<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Trim as PHPSpreadsheetTrim;

final class Trim implements CustomFunction
{
    public function getName(): string
    {
        return 'TRIM';
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
     * @return array|string
     */
    public static function compute()
    {
        $str = Flatten::singleValue(func_get_arg(0));

        return PHPSpreadsheetTrim::spaces($str);
    }
}
