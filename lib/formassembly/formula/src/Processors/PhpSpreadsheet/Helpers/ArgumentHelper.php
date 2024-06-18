<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers;

final class ArgumentHelper
{
    /**
     * Returns the argument in the determined position
     * from the list if it exists, otherwise returns the
     * default value.
     *
     * @param array $argumentList
     * @param int $argumentPosition
     * @param mixed $defaultValue
     * 
     * @return mixed
     */
    public static function getArgument(array $argumentList, int $argumentPosition, $defaultValue = null)
    {
        if (count($argumentList) < ($argumentPosition + 1)) {
            return $defaultValue;
        }

        return $argumentList[$argumentPosition];
    }
}
