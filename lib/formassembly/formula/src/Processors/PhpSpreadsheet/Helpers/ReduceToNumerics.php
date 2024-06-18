<?php

declare(strict_types=1);

namespace FormAssembly\Formula\Processors\PhpSpreadsheet\Helpers;

final class ReduceToNumerics
{
    public static function givenValues(array $values): array
    {
        return FilterNonNumericStrings::filter(ConvertToNumericIfApplicable::tryAll($values));
    }
}
