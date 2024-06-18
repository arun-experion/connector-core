<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Conditional;

final class IfError implements CustomFunction
{
    public function getName(): string
    {
        return 'IFERROR';
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
     * @return mixed
     */
    public static function compute()
    {
        $testValue = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0));
        $errorPart = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1));

        $testValue = ($testValue === null) ? '' : Flatten::singleValue($testValue);
        $errorpart = ($errorPart === null) ? '' : Flatten::singleValue($errorPart);

        return Conditional::statementIf(Functions::isError($testValue), $errorpart, $testValue);
    }
}
