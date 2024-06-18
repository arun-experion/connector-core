<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Search;

final class Find implements CustomFunction
{
    public function getName(): string
    {
        return 'FIND';
    }

    public function getArgumentCount(): string
    {
        return '2,3';
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
        // Customization to return #VALUE! when needle is empty
        // PHP Spreadsheet returns 1 for empty needle, while PHP Excel returns #VALUE!
        $needle = Flatten::singleValue(func_get_arg(0) ?? null);
        if (empty($needle)) {
            return ExcelError::VALUE();
        }

        return Search::sensitive(...func_get_args());
    }
}
