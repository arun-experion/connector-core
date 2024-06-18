<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class Right implements CustomFunction
{
    public function getName(): string
    {
        return 'RIGHT';
    }

    public function getArgumentCount(): string
    {
        return '1+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): string
    {
        $str = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0)) ?: 0;
        $chrs = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1)) ?: 0;

        if (!is_numeric($chrs)) {
            $chrs = 0;
        }

        $str = $str ?: '';

        return mb_substr($str, strlen($str) - $chrs, null, "UTF-8");
    }
}
