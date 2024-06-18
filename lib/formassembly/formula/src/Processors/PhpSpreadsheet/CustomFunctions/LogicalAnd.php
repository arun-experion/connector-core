<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ConvertToNumericIfApplicable;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Operations;

final class LogicalAnd implements CustomFunction
{
    public function getName(): string
    {
        return 'AND';
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
     * @return bool|string
     */
    public static function compute()
    {
        return Operations::logicalAnd(ConvertToNumericIfApplicable::tryAll(Flatten::anArray(func_get_args())));
    }
}
