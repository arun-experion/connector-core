<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\CharacterConvert;

final class Char implements CustomFunction
{
    public function getName(): string
    {
        return 'CHAR';
    }

    public function getArgumentCount(): string
    {
        return '0+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    public static function compute()
    {
        // Customization to return char(0) when input is empty or not string
        $arg = Flatten::singleValue(func_get_arg(0) ?? null);
        if (empty($arg) || !is_numeric($arg)) {
            return chr(0);
        }

        $arg = (int)$arg;

        return CharacterConvert::character($arg);
    }
}
