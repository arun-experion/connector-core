<?php

namespace Connector\Operation;

use Connector\Record;
use Connector\Record\RecordKey;
use Connector\Record\Recordset;

/**
 * The Result of an Operation. Contains information about the data extracted and loaded.
 */
final class Result
{
    private Recordset  $extractedRecordSet;
    private RecordKey $loadedRecordKey;
    private Recordset $returnedRecordSet;
    private array $log = [];

    public function __construct()
    {
        $this->extractedRecordSet = new Recordset();
        $this->returnedRecordSet  = new Recordset();
        $this->loadedRecordKey    = new RecordKey(null);
    }

    /**
     * Get the collection of Records extracted from the source
     * @return \Connector\Record\Recordset
     */
    public function getExtractedRecordSet(): Recordset
    {
        return $this->extractedRecordSet;
    }

    /**
     * Set the collection of Records extracted from the source
     * @param \Connector\Record\Recordset $extractedRecordSet
     *
     * @return \Connector\Operation\Result
     */
    public function setExtractedRecordSet(Recordset $extractedRecordSet): self
    {
        $this->extractedRecordSet = $extractedRecordSet;
        return $this;
    }

    /**
     * Get the key (implementation-specific unique record ID) of the record that was loaded in the target
     * @return \Connector\Record\RecordKey
     */
    public function getLoadedRecordKey(): RecordKey
    {
        return $this->loadedRecordKey;
    }

    /**
     * Set the key (implementation-specific unique record ID) of the record that was loaded in the target
     * @param \Connector\Record\RecordKey $loadedRecordKey
     *
     * @return \Connector\Operation\Result
     */
    public function setLoadedRecordKey(?RecordKey $loadedRecordKey): self
    {
        if($loadedRecordKey) {
            $this->loadedRecordKey = $loadedRecordKey;
        }
        return $this;
    }


    /**
     * @return \Connector\Record\Recordset
     */
    public function getReturnedRecordSet(): Recordset
    {
        return $this->returnedRecordSet;
    }

    /**
     * @param \Connector\Record\Recordset $returnedRecordSet
     *
     * @return \Connector\Operation\Result
     */
    public function setReturnedRecordSet(?Recordset $returnedRecordSet): self
    {
        if($returnedRecordSet) {
            $this->returnedRecordSet = $returnedRecordSet;
        }
        return $this;
    }

    public function hasReturnedRecords(): bool
    {
        return count($this->returnedRecordSet) > 0;
    }

    /**
     * Returns the record key (implementation-specific unique ID) of the first record extracted from the source (if any).
     * Note that multiple records may be extracted. This returns only the first record key.
     * @return \Connector\Record\RecordKey|null
     */
    public function getExtractedRecordKey(): ?RecordKey
    {
        return $this->extractedRecordSet[0]?->getKey();
    }

    /**
     * Returns the log produced by the operation (includes log produced by source and target integrations)
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Add a message or array of messages to the Operation log
     * @param string|string[] $messages
     *
     * @return void
     */
    public function log(mixed $messages): void
    {
        if(!empty($messages)) {
            if(is_array($messages)) {
                $this->log = array_merge($this->log, $messages);
            }
            else {
                $this->log[] = $messages;
            }
        }
    }
}
