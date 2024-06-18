<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;

final class Flatten
{
    public static function anArray(array $array): array
    {
        return Functions::flattenArray($array);
    }

    public static function anArrayIndexed(array $array): array
    {
        return Functions::flattenArrayIndexed($array);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function singleValue($value)
    {
        while (is_array($value)) {
            $value = array_pop($value);
        }

        return $value;
    }
}
