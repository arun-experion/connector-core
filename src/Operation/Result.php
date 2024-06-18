<?php

namespace Connector\Operation;

use Connector\Record\RecordKey;
use Connector\Record\Recordset;

final class Result
{
    private Recordset $extractedRecordSet;
    private RecordKey $loadedRecordKey;
    private Recordset $returnedRecordSet;

    public function __construct()
    {
        $this->extractedRecordSet = new Recordset();
        $this->returnedRecordSet  = new Recordset();
        $this->loadedRecordKey    = new RecordKey(null);
    }

    /**
     * @return \Connector\Record\Recordset
     */
    public function getExtractedRecordSet(): Recordset
    {
        return $this->extractedRecordSet;
    }

    /**
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
     * @return \Connector\Record\RecordKey
     */
    public function getLoadedRecordKey(): RecordKey
    {
        return $this->loadedRecordKey;
    }

    /**
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

    public function getExtractedRecordKey(): ?RecordKey
    {
        return $this->extractedRecordSet[0]?->getKey();
    }
}