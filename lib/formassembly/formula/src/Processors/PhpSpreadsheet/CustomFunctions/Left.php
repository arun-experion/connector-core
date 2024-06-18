<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ArgumentHelper;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use Exception;

final class Left implements CustomFunction
{
    public function getName(): string
    {
        return 'LEFT';
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
        $chars = Flatten::singleValue(ArgumentHelper::getArgument(func_get_args(), 1));

        if (is_null($chars)) {
            $chars = strlen($value ?? '');
        } else if ($chars === "") {
            $chars = 0;
        } else if (!is_numeric($chars)) {
            $chars = 0;
        }

        return mb_substr((string)($value ?? ''), 0, $chars, 'UTF-8');
    }
}
