<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

/**
 * Pass-through function.
 * Allows arithmetic expressions to be evaluated in a context where formulas are mixed with non-evaluated content.
 */
final class Compute implements CustomFunction
{
    public function getName(): string
    {
        return 'COMPUTE';
    }

    public function getArgumentCount(): string
    {
        return '1';
    }

    public function shouldPassCellReference(): bool
    {
        return false;
    }

    public static function compute(): mixed
    {
        return func_get_arg(0);
    }
}
