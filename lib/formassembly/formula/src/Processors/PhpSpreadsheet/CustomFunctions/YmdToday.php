<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

final class YmdToday implements CustomFunction
{
    public function getName(): string
    {
        return 'YMDTODAY';
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
        $format = $args[1] ?? null;
        if (!$format) {
            $format = 'Y-m-dP';
        }

        return (new YmdNow())->compute($args[0] ?? null, $format);
    }
}
