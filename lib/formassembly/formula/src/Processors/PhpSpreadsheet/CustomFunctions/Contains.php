<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function strlen;
use function strpos;

final class Contains implements CustomFunction
{
    public function getName(): string
    {
        return 'CONTAINS';
    }

    public function getArgumentCount(): string
    {
        return '2-3';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): bool
    {
        $firstArg = func_get_arg(0);
        $secArg = func_get_arg(1);

        $needle = (string) Flatten::singleValue($firstArg);
        $haystack = (string) Flatten::singleValue($secArg);
        $offset = func_num_args() === 3 ? Flatten::singleValue(func_get_arg(2)) : 1;

        if (($offset > 0) && (strlen($haystack) > $offset) && !empty($needle) && !empty($haystack)) {
            $pos = strpos($haystack, $needle, --$offset);
            if ($pos !== false) {
                return true;
            }
        }
        return false;
    }
}
