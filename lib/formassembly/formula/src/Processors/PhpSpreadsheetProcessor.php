<?php

namespace FormAssembly\Formula\Processors;

use FormAssembly\Formula\Exceptions\FormulaNotFoundException;
use FormAssembly\Formula\Exceptions\FormulaSyntaxErrorException;
use FormAssembly\Formula\Processors\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use Error;
use Exception;
use PhpOffice\PhpSpreadsheet\Calculation\FormulaParser;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PhpSpreadsheetProcessor implements ProcessorInterface
{
    private PhpSpreadsheet $library;
    private static int $_calcIndex     = 1; // prevents interference from previous calculations
    private static int $_variableIndex = 1; // prevents interference from previous calculations

    /**
     * @param string|string[]|null $formula Note that empty strings in JSON are forced to a null value by
     *                                      ConvertEmptyStringsToNull middleware in Laravel. If the middleware
     *                                      is removed, the null type can be removed.
     *
     * @return string|string[]
     */
    public function evaluate(string|array|null $formula, array $variables=[]): string|array
    {
        if($formula === null || $formula === '') {
            return '';
        }

        if (is_array($formula)) {
            $result = [];
            foreach ($formula as &$value) {
                $result[] = $this->evaluate($value, $variables);
            }
        }
        else {

            $result = '';
            $tokens = $this->tokenize($formula);

            foreach ($tokens as $token) {

                if ($this->hasFunction($token)) {

                    $token = $this->preProcess($token);

                    foreach($variables as $variableName => $variableValue) {
                        $token = $this->replaceAliasWithCell($token, $variableName, $variableValue);
                    }

                    // prevents interference from previous calculations by using a unique cell each time.
                    $cell = 'A' . self::$_calcIndex;
                    $this->setCellValue($cell, "=" . $token);

                    try {
                        $token = $this->getCellCalculatedValue($cell);
                    } catch (\Throwable $exception) {
                        $token = "#VALUE!";
                    } finally {
                        self::$_calcIndex = self::$_calcIndex + 1;
                    }
                }

                // Resolve any leftover aliases, in case no function was evaluated.
                foreach($variables as $variableName => $variableValue) {
                    $token = str_replace("%%" . $variableName . "%%", $variableValue, $token);
                }

                // Clean any leftover alias that wasn't defined as a variable
                $token = preg_replace("/%%.+%%/U", "", $token);

                $result .= $token;
            }
        }
        return $result;
    }

    private function replaceAliasWithCell(string $formula, string $variableName, string $variableValue): string
    {
        $cell = $this->setVariableCell($variableName, $variableValue);
        return str_replace("%%" . $variableName . "%%", $cell, $formula);
    }

    private function setVariableCell(string $variableName, string $variableValue): string
    {
        $cell = 'B' . self::$_variableIndex++;
        $this->setCellValue($cell, $variableValue);
        return $cell;
    }

    /**
     * Sets the cell value in the active spreadsheet
     *
     * @param string $index
     * @param string $value
     *
     * @return void
     */
    private function setCellValue(string $index, string $value): void
    {
        $activeSheet = $this->getLibrary()->getActiveSheet();

        // PHP Spreadsheet remove trailing zeros when we set a cell with a float number.
        // So, when we set a float value, we need to explicity set the data type of the cell to string.
        // But if it's a formula, we can't do that, otherwise it will be evaluated as a string,
        // and will not be calculated.

        // Checks if we are trying to set a formula value
        if (str_starts_with(trim((string)$value), '=')) {
            $activeSheet->setCellValue($index, $value);
        } else {
            // We have a behavior change where with numbers with trailing zeros
            $activeSheet->setCellValueExplicit($index, $value, DataType::TYPE_STRING);
        }
    }

    /**
     * Get an instance of the library
     *
     * @return mixed
     */
    private function getLibrary()
    {
        return $this->library ??= new PhpSpreadsheet();
    }

    /**
     * Gets a list of valid and supported function names
     *
     * @return array
     */
    public function listSupportedFunctions(): array
    {
        return $this->getLibrary()->getCalculationEngine()->getInstance()->getImplementedFunctionNames();
    }

    /**
     * Gets a list of valid and supported function names
     *
     * @param mixed $index
     *
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     */
    private function getCellCalculatedValue($index): mixed
    {
        return $this->getLibrary()
            ->getActiveSheet()
            ->getCell($index)
            ->getCalculatedValue();
    }

    /**
     * Runs a parser to validate the formula
     *
     * @param string $formula
     *
     * @return void
     *
     * @throws FormulaSyntaxErrorException|FormulaNotFoundException
     */
    public function validate(string $formula): void
    {
        try {
            $parser = new FormulaParser($formula);

            //Always use new instance to avoid caching issue with failed formulas.
            (new PhpSpreadsheet())->getCalculationEngine()->parseFormula($formula);
        } catch (Error | Exception $e) {
            throw new FormulaSyntaxErrorException("Unable to parse formula", $e->getCode(), $e);
        }

        if (!$parser->getTokenCount()) {
            throw new FormulaNotFoundException('Parse error, no formula found');
        }
    }

    /**
     * Ensure formula can be properly parsed by formula engine.
     * Matches behavior implemented in FA main repo.
     * @param string $formula
     *
     * @return string
     */
    private function preProcess(string $formula): string
    {
        // Decode HTML entities
        // https://git.formassembly.com/Formassembly/formassembly/blob/8f8e88407cc569638e03cc65b6ae5b3297ad35a0/api_v2/app/Formula/Traits/FormulaProcessorTrait.php#L150
        $formula = html_entity_decode($formula, ENT_QUOTES, 'UTF-8');

        // Standardize line breaks
        // https://git.formassembly.com/Formassembly/formassembly/blob/8f8e88407cc569638e03cc65b6ae5b3297ad35a0/api_v2/app/Formula/Traits/FormulaProcessorTrait.php#L153
        $formula = str_replace("\r\n", "\n", $formula);

        // Replace non-breaking spaces
        // https://git.formassembly.com/Formassembly/formassembly/blob/8f8e88407cc569638e03cc65b6ae5b3297ad35a0/api_v2/app/Formula/Traits/FormulaProcessorTrait.php#L167
        $formula = str_replace("\xc2\xa0", ' ', $formula);

        // Replace left/right double-quotes with straight double-quotes
        $formula = str_replace("\u{201C}", '"', $formula);
        $formula = str_replace("\u{201D}", '"', $formula);

        // Remove pairs of square brackets [] only if they are not within double quotes.
        // The positive lookahead assertion ensures that the square brackets are not inside a pair of double quotes by checking that, from the current position, there are an even number of double quotes ahead in the string.
        // https://git.formassembly.com/Formassembly/formassembly/blob/8f8e88407cc569638e03cc65b6ae5b3297ad35a0/api_v2/app/Formula/Traits/FormulaProcessorTrait.php#L293
        $formula = preg_replace('/\[\](?=(?:[^"]*"[^"]*")*[^"]*$)/', 'null', $formula);

        return $formula;
    }


    /**
     * This tokenizer separates formulas from arbitrary text. Formulas can then be processed by PhpSpreadsheetProcessor.
     * Does not tokenize nested formulas, as PhpSpreadsheetProcessor can do that on its own.
     *
     * @param string $formula
     *
     * @return string[] returns an array of arbitrary content and formulas
     */
    public function tokenize(string $formula): array
    {
        $tokens  = [];
        $token   = '';
        $func    = [];
        $str     = "";

        for($i=0;$i < strlen($formula); $i++) {
            $token .= $formula[$i];
            if($this->startsFunction($token)) {
                $offset = strrpos($token, "@");
                if(count($func) === 0 && $offset >0) {
                    $tokens[] = substr($token, 0, $offset);
                }
                for($j=0; $j < count($func); $j++) {
                    $func[$j] .= $formula[$i];
                }
                $func[] = substr($token, $offset);
                $token  = "";
            }
            elseif(!$str && $this->startString($token)) {
                for($j=0; $j < count($func); $j++) {
                    $func[$j] .= $formula[$i];
                }
                $str = $formula[$i];
            }
            elseif($this->endsString($str, $token)) {
                for($j=0; $j < count($func); $j++) {
                    $func[$j] .= $formula[$i];
                }
                $str = "";
            }
            elseif(!$str && $this->endsFunction($func, $token)) {
                for($j=0; $j < count($func); $j++) {
                    $func[$j] .= $formula[$i];
                }
                if(count($func) === 1) {
                    $tokens[] = array_pop($func);
                } else {
                    array_pop($func);  // decrease depth, and discard nested function.
                }
                $token = "";
            }
            elseif(count($func)>0) {
                $func[0] .= $formula[$i];
            }
        }

        if(count($func) > 0) {
            // Invalid syntax. non-terminated function call.
            $tokens[] = array_shift($func);
            $token = "";
        }

        if($token) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    private function hasFunction(?string $expression): bool
    {
        // Strict detection. Function name must be valid.
        if($expression) {
            $functions = $this->listSupportedFunctions();
            foreach ($functions as $function) {
                if (str_contains($expression, "@" . $function . "(")) {
                    return true;
                }
            }
        }
        return false;

    }

    private function startsFunction(?string $expression): bool
    {
        return $this->hasFunction($expression);
    }

    private function endsFunction(array $func, ?string $token): bool
    {
        if($token && count($func)>0 && str_ends_with($token, ")")) {
            return true;
        }
        return false;
    }

    private function startString(?string $token): bool
    {
        if($token && (str_ends_with($token, "'") || str_ends_with($token, "\""))) {
            return true;
        }
        return false;
    }

    private function endsString(string $str, ?string $token): bool
    {
        $str = $str . $token;
        if(str_starts_with($str, "\"") && !str_ends_with($str, "\\\"") && str_ends_with($str, "\"")) {
            return true;
        }
        if(str_starts_with($str, "'") && !str_ends_with($str, "\\'") && str_ends_with($str, "'")) {
            return true;
        }
        return false;
    }

}
