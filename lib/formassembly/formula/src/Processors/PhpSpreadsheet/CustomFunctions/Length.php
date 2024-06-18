<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function mb_strlen;

final class Length implements CustomFunction
{
    public function getName(): string
    {
        return 'LEN';
    }

    public function getArgumentCount(): string
    {
        return '1';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): string
    {
        $value = (string)Flatten::singleValue(func_get_arg(0));

        return (string)($value ? mb_strlen($value) : 0);
    }
}
