<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function mb_convert_case;

use const MB_CASE_TITLE;

final class Proper implements CustomFunction
{
    public function getName(): string
    {
        return 'PROPER';
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

        //match php excel behavior
        if (empty($arg)) {
            return '';
        }

        $arg = Flatten::singleValue($arg);

        return mb_convert_case((string)$arg, MB_CASE_TITLE, 'UTF-8');
    }
}
