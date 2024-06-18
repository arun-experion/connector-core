<?php

namespace Connector\Integrations\Spreadsheet;

use Connector\Record\RecordLocator;

/**
 * Note: Optional properties must accept a null value.
 */
class SpreadsheetRecordLocator extends RecordLocator
{
    /**
     * @var string $recordType
     * Spreadsheet file identifier. Implementation-specific.
     */
    public string $recordType  = '';

    /**
     * @var string|null Sheet name
     */
    public ?string $sheet = 'Sheet1';

    /**
     * @var string|null $range - A range to extract data from. Implementation-specific. E.g. "Sheet1:A2:D5"
     */
    public ?string $range = null;


    public function getSheetName(): string
    {
        return $this->sheet ?? 'Sheet1';
    }
}
