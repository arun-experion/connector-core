<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use function strftime;

final class LocalNow implements CustomFunction
{
    public function getName(): string
    {
        return 'LOCALNOW';
    }

    public function getArgumentCount(): string
    {
        return '0';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    /**
     * @return string|false
     */
    public static function compute()
    {
        return strftime('%x %H:%M');
    }
}
