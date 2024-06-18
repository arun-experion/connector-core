<?php

namespace Connector\Integrations;

use Connector\Record\RecordKey;
use Connector\Record\Recordset;

/**
 * Class representing the result of the execution of an integration.
 */
class Response
{
    public ?string $errorCode = null;
    public ?string $errorMessage = null;
    public ?RecordKey $recordKey= null;
    public ?Recordset $recordset = null;

    /**
     * Sets the list of records, either found as the result of an extract(),
     * or constructed as the result of the load()
     *
     * @param \Connector\Record\Recordset $recordset
     *
     * @return $this
     */
    public function setRecordset(Recordset $recordset): self
    {
        $this->recordset = $recordset;
        return $this;
    }

    /**
     * Sets the key of the created or updated record as the result of a load()
     * @param \Connector\Record\RecordKey $recordKey
     *
     * @return $this
     */
    public function setRecordKey(RecordKey $recordKey): self
    {
        $this->recordKey = $recordKey;
        return $this;
    }

    /**
     * Set the error code and message to be passed back to the connector after
     * an error in the integration.
     *
     * @param string $code
     * @param string $message
     *
     * @return $this
     */
    public function setError(string $code, string $message): self
    {
        $this->errorCode = $code;
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * @return \Connector\Record\Recordset|null
     */
    public function getRecordset(): ?Recordset
    {
        return $this->recordset;
    }

    /**
     * @return \Connector\Record\RecordKey|null
     */
    public function getRecordKey(): ?RecordKey
    {
        return $this->recordKey;
    }
}