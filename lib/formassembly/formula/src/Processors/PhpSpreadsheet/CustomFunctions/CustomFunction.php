<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

/**
 * We have added functionality on top of the PhpSpreadsheet library. We have added
 * several functions that Excel does not natively support and have overridden several
 * others with custom functionality.
 *
 * Most of the functionality changes are related to how booleans are handled. In Excel
 * numeric strings will cause errors when evaluating boolean logic.
 *
 * Example: =OR("1") will throw error "#VALUE!" as it is not considered a boolean value.
 *
 * We have enhanced several functions to cast strings to numbers to allow proper evaluation
 * of boolean values within PhpSpreadsheet.
 */
interface CustomFunction
{
    public function getName(): string;

    public function getArgumentCount(): string;

    public function shouldPassCellReference(): bool;

    /**
     * @return mixed
     */
    public static function compute();
}
