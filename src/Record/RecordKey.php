<?php
declare(strict_types=1);
namespace Connector\Record;

/**
 * Base class for the implementation-specific RecordKey.
 * The RecordKey uniquely identifies a single record in the integrated system.
 */
class RecordKey
{
    public mixed  $recordId;
    public string $recordType;

    public function __construct(mixed $recordId, $recordType = 'default')
    {
        $this->recordId   = $recordId;
        $this->recordType = $recordType;
    }
}
