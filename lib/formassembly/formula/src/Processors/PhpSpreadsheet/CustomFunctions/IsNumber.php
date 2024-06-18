<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function is_null;

final class IsNumber implements CustomFunction
{
    public function getName(): string
    {
        return 'ISNUMBER';
    }

    public function getArgumentCount(): string
    {
        return '1';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): bool
    {
        $value = func_get_arg(0);
        if (!is_null($value)) {
            $value = Flatten::singleValue($value);
        }

        return is_numeric($value);
    }
}
