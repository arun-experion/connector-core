<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function date;
use function strtotime;

final class YmdNow implements CustomFunction
{
    public function getName(): string
    {
        return 'YMDNOW';
    }

    public function getArgumentCount(): string
    {
        return '0-2';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): string
    {
        $args = func_get_args();
        $offset = (string)Flatten::singleValue($args[0] ?? null);
        $format = $args[1] ?? null;
        $timestamp = (bool)strtotime($offset) ? strtotime($offset) : 0;

        if (is_array($format)) {
            return "";
        }

        if (empty($format)) {
            $format = 'Y-m-d\TH:i:sP';
        }

        return empty($offset) ? date($format) : date($format, $timestamp);
    }
}
