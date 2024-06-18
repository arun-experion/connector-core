<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function addslashes;

final class AddSlashes implements CustomFunction
{
    public function getName(): string
    {
        return 'ADDSLASHES';
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

        return addslashes((string) Flatten::singleValue($arg));
    }
}
