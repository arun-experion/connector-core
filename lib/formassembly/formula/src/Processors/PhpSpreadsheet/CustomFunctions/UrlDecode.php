<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

use function rawurldecode;

final class UrlDecode implements CustomFunction
{
    public function getName(): string
    {
        return 'URLDECODE';
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
        return rawurldecode(Flatten::singleValue(func_get_arg(0)));
    }
}
