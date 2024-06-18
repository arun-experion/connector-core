<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;

final class Quotient implements CustomFunction
{
    public function getName(): string
    {
        return 'QUOTIENT';
    }

    public function getArgumentCount(): string
    {
        return '2+';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): int
    {
        $args = func_get_args();
        $returnValue = null;

        // Loop through arguments
        foreach (Flatten::anArray($args) as $arg) {
            // Is it a numeric value?
            if (is_numeric($arg)) {
                // Force convert to number (int or float)
                $arg = $arg + 0;
                if ($returnValue === null) {
                    $returnValue = ($arg == 0) ? 0 : $arg;
                } else {
                    if (($returnValue == 0) || ($arg == 0)) {
                        $returnValue = 0;
                    } else {
                        $returnValue /= $arg;
                    }
                }
            }
        }

        return (int) $returnValue;
    }
}
