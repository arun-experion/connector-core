<?php

namespace Connector\Integrations\Spreadsheet;

use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;

/**
 * Abstract class for spreadsheet-like integrations (Google Sheets, Excel 360, etc.)
 * Redefines load() as a spreadsheet creation and row insertion, and extract() as a getting data from a sheet range.
 *
 * Expects RecordLocator to have the following properties:
 *  `recordType` - identifies the spreadsheet being updated, created, or read. Value is implementation-specific.
 *  `sheet`      - identifies the sheet in the spreadsheet being updated, created, or read. Value is implementation-specific.
 *  `range`      - identifies the range of data to read from. Required when extracting data. Value is implementation-specific.
 *
 * Mapping keys are ignored. Values are inserted from left to right, starting at the first column of the sheet.
 */
abstract class AbstractSpreadsheet extends AbstractIntegration
{
    static private array $batch = [];

    abstract protected function createOrFindSpreadsheet(string $fileName): mixed;
    abstract protected function createOrFindSheet(mixed $file, string $sheetName, array $columnHeaders): mixed;
    abstract protected function insertRows($file, $sheet, $rows);
    abstract protected function getRows($file, $sheet, $range): Recordset;
    abstract protected function getFileName(mixed $file): string;

    final public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        $recordLocator = new SpreadsheetRecordLocator($recordLocator);
        $file      = $recordLocator->recordType;
        $sheet     = $recordLocator->getSheetName();
        $range     = $recordLocator->range;
        $recordset = $this->getRows($file, $sheet, $range);
        return (new Response())->setRecordset($recordset);
    }

    final public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        $recordLocator = new SpreadsheetRecordLocator($recordLocator);
        $file    = $recordLocator->recordType;
        $sheet   = $recordLocator->getSheetName();
        $headers = $this->getColumnHeaders($mapping);
        $record  = $this->getRow($mapping);
        $key     = $this->batch($file, $sheet, $headers, $record);
        return (new Response())->setRecordKey($key);
    }

    final public function begin(): void
    {
        self::$batch = [];
    }

    final public function end(): void
    {
        foreach(self::$batch as $fileName => $sheets) {
            $file = $this->createOrFindSpreadsheet($fileName);
            foreach($sheets as $sheetName => $rows) {
                $headers = array_shift($rows);
                $sheet   = $this->createOrFindSheet($file, $sheetName, $headers);
                $this->insertRows($file, $sheet, $rows);
                $this->log(sprintf('Inserted %d row(s) in sheet "%s" of spreadsheet "%s"', count($rows),
                    $sheetName, $this->getFileName($file)));
            }
        }
    }

    protected function getColumnHeaders(Mapping $mapping): array
    {
        return array_map(function($item) { return $item->label ?? ''; }, $mapping->items);
    }

    protected function getRow(Mapping $mapping): array
    {
        return array_values(array_map(function($item) { return $item->value; }, $mapping->items));
    }

    private function batch(string $path, string $sheet, array $headers, array $record): RecordKey
    {
        if(!isset(self::$batch[$path][$sheet])) {
            self::$batch[$path][$sheet] = [$headers];
        }
        self::$batch[$path][$sheet][] = $record;
        $index = count(self::$batch[$path][$sheet]);
        return new RecordKey($index);
    }

}
