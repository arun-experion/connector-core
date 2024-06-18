<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function rawurlencode;

final class UrlEncode implements CustomFunction
{
    public function getName(): string
    {
        return 'URLENCODE';
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
        return rawurlencode((string)Flatten::singleValue(func_get_arg(0)));
    }
}
