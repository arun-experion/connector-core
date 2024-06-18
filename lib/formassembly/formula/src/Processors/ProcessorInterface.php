<?php

namespace FormAssembly\Formula\Processors;

use FormAssembly\Formula\Exceptions\FormulaNotFoundException;
use FormAssembly\Formula\Exceptions\FormulaSyntaxErrorException;

interface ProcessorInterface
{

    /**
     * @param string|string[] $formula
     * @param array           $variables Associative array of key/value pairs.
     *
     * @return string|string[]
     */
    public function evaluate(string|array $formula, array $variables=[]): string|array;

    /**
     * Gets a list of valid and supported function names
     *
     * @return array
     */
    public function listSupportedFunctions(): array;


    /**
     * Runs a parser to validate the formula
     *
     * @param string $formula
     *
     * @return void
     *
     * @throws FormulaSyntaxErrorException|FormulaNotFoundException
     */
    public function validate(string $formula): void;
}
