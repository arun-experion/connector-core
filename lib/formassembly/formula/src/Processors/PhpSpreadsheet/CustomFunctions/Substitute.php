<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function str_replace;

final class Substitute implements CustomFunction
{
    public function getName(): string
    {
        return 'SUBSTITUTE';
    }

    public function getArgumentCount(): string
    {
        return '3';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return string|string[]
     */
    public static function compute()
    {
        $firstArg = func_get_arg(0);

        $text = (string)Flatten::singleValue($firstArg);
        $from = (string)Flatten::singleValue(func_get_arg(1));
        $to = (string)Flatten::singleValue(func_get_arg(2));

        return str_replace($from, $to, $text);
    }
}
