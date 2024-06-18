<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\CustomFunctions;

use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\Flatten;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers\ReduceToNumerics;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

final class Subtotal implements CustomFunction
{
    public function getName(): string
    {
        return 'SUBTOTAL';
    }

    public function getArgumentCount(): string
    {
        return '2+';
    }

    public function shouldPassCellReference(): bool
    {
        return true;
    }

    /**
     * @return float|string
     */
    public static function compute()
    {
        $args = func_get_args();
        $function = array_shift($args);

        /** @var Cell $cell */
        $cell = array_pop($args);
        $reducedArgs = ReduceToNumerics::givenValues(Flatten::anArrayIndexed($args));
        $cell->setValue(self::createCellFormula($reducedArgs));

        return \PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Subtotal::evaluate(
            $function,
            $reducedArgs,
            $cell
        );
    }

    private static function createCellFormula(array $args): string
    {
        $args = implode(', ', $args);

        return "=SUBTOTAL({$args})";
    }
}
