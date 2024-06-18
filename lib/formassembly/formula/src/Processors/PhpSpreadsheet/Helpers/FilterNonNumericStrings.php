<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers;

final class FilterNonNumericStrings
{
    public static function filter(array $values): array
    {
        return array_filter($values, 'is_numeric');
    }
}
