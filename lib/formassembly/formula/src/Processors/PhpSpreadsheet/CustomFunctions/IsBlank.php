<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function is_null;

final class IsBlank implements CustomFunction
{
    public function getName(): string
    {
        return 'ISBLANK';
    }

    public function getArgumentCount(): string
    {
        return '0,1';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): bool
    {
        if (count(func_get_args()) === 0) {
            return true;
        }

        $value = func_get_arg(0);
        if (!is_null($value)) {
            $value = Flatten::singleValue($value);
        }

        return is_null($value) || $value === "";
    }
}
