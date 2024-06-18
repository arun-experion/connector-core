<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use Exception;

final class Mid implements CustomFunction
{
    public function getName(): string
    {
        return 'MID';
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
        $value = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 0));
        $start = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1, 1));
        $chars = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 2));

        try {
            $value = (string)Flatten::singleValue($value);
            $start = Flatten::singleValue($start);
            $chars = Flatten::singleValue($chars);

            if (!is_numeric($start)) {
                $start = 1;
            }

            if (!is_numeric($chars)) {
                $chars = null;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return mb_substr($value ?: '', --$start, $chars, 'UTF-8');
    }
}
