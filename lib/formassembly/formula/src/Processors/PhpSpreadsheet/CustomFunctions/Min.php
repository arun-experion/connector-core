<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ConvertToNumericIfApplicable;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Minimum;

final class Min implements CustomFunction
{
    public function getName(): string
    {
        return 'MIN';
    }

    public function getArgumentCount(): string
    {
        return '1+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): float
    {
        return Minimum::min(ConvertToNumericIfApplicable::tryAll(Flatten::anArray(func_get_args())));
    }
}
