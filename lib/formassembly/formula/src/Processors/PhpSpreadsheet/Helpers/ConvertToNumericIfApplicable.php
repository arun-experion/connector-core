<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers;

use function is_numeric;
use function is_string;
use function preg_match;

final class ConvertToNumericIfApplicable
{
    public static function tryAll(array $values): array
    {
        return array_map([self::class, 'try'], $values);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function try($value)
    {
        if (!is_numeric($value) && is_string($value)) {
            return $value;
        }

        // See FA-4082. Convert a numeric string to a typed number value.
        // It is important to distinguish between integers and floats,
        // as big integers, if converted to floats, will be formatted with using scientific
        // notation when used in string functions.
        return preg_match('/^-?[0-9]+$/is', (string) $value) ? (int) $value : (float) $value;
    }
}
