<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class Text implements CustomFunction
{
    public function getName(): string
    {
        return 'TEXT';
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
        $arg = func_get_arg(0);

        return (string) Flatten::singleValue($arg);
    }
}
