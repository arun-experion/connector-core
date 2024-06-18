<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function strtolower;

final class Lower implements CustomFunction
{
    public function getName(): string
    {
        return 'LOWER';
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
        if (empty($arg)) {
            return "";
        }

        return strtolower((string)Flatten::singleValue($arg));
    }
}
